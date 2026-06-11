import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const dashboardDuration = new Trend('dashboard_duration', true);
const lansiaDuration = new Trend('lansia_list_duration', true);

// Test configuration
export const options = {
    scenarios: {
        // Ramp up to 10 concurrent users over 30s, hold for 1 min, ramp down
        load_test: {
            executor: 'ramping-vus',
            startVUs: 1,
            stages: [
                { duration: '15s', target: 5 },   // ramp up to 5
                { duration: '30s', target: 10 },  // ramp to 10
                { duration: '30s', target: 10 },  // hold at 10
                { duration: '15s', target: 0 },   // ramp down
            ],
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<5000'],  // 95% of requests under 5s
        errors: ['rate<0.1'],                // error rate below 10%
        dashboard_duration: ['p(95)<8000'], // dashboard under 8s (heavy query)
        lansia_list_duration: ['p(95)<3000'], // list under 3s
    },
};

const BASE_URL = 'http://127.0.0.1:8080';

// Login and get session cookie
function login() {
    // 1. Get login page (CSRF token)
    const loginPage = http.get(`${BASE_URL}/login`);
    const csrfMatch = loginPage.body.match(/name="_token"\s+value="([^"]+)"/);
    
    if (!csrfMatch) {
        console.error('Could not extract CSRF token');
        return null;
    }

    const csrfToken = csrfMatch[1];

    // 2. POST login
    const loginRes = http.post(`${BASE_URL}/login`, {
        _token: csrfToken,
        email: 'admin@lansiapapua.id',
        password: 'password123!',
    }, {
        redirects: 0,
    });

    // Should redirect (302) on success
    if (loginRes.status !== 302) {
        console.error(`Login failed: status ${loginRes.status}`);
        return null;
    }

    return loginRes.cookies;
}

export default function () {
    // Login once per VU iteration
    const jar = http.cookieJar();
    
    group('01_health_check', function () {
        const res = http.get(`${BASE_URL}/health`);
        check(res, {
            'health status 200': (r) => r.status === 200,
            'health is healthy': (r) => r.json('status') === 'healthy',
        }) || errorRate.add(1);
    });

    sleep(0.5);

    group('02_welcome_page', function () {
        const res = http.get(`${BASE_URL}/`);
        check(res, {
            'welcome status 200': (r) => r.status === 200,
            'welcome has content': (r) => r.body.includes('JALAN'),
        }) || errorRate.add(1);
    });

    sleep(0.5);

    // Login
    group('03_login', function () {
        const loginPage = http.get(`${BASE_URL}/login`);
        const csrfMatch = loginPage.body.match(/name="_token"\s+value="([^"]+)"/);
        
        if (!csrfMatch) {
            errorRate.add(1);
            return;
        }

        const res = http.post(`${BASE_URL}/login`, {
            _token: csrfMatch[1],
            email: 'admin@lansiapapua.id',
            password: 'password123!',
        });

        check(res, {
            'login redirects or OK': (r) => r.status === 200 || r.status === 302,
        }) || errorRate.add(1);
    });

    sleep(1);

    // Dashboard (heaviest endpoint)
    group('04_dashboard', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app`);
        dashboardDuration.add(Date.now() - start);

        check(res, {
            'dashboard status 200': (r) => r.status === 200,
            'dashboard has stats': (r) => r.body.includes('Total Responden'),
        }) || errorRate.add(1);
    });

    sleep(1);

    // Lansia list
    group('05_lansia_list', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app/lansia`);
        lansiaDuration.add(Date.now() - start);

        check(res, {
            'lansia list status 200': (r) => r.status === 200,
            'lansia has data': (r) => r.body.includes('Data Lansia') || r.body.includes('questionnaire'),
        }) || errorRate.add(1);
    });

    sleep(1);

    // Dashboard with filter
    group('06_dashboard_filtered', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app?gender=male`);
        dashboardDuration.add(Date.now() - start);

        check(res, {
            'filtered dashboard 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(1);

    // Lansia search
    group('07_lansia_search', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app/lansia?search=Demo`);
        lansiaDuration.add(Date.now() - start);

        check(res, {
            'search status 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(0.5);
}
