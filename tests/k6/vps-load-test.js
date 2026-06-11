import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// ═══════════════════════════════════════════
// KONFIGURASI — sesuaikan sebelum run
// ═══════════════════════════════════════════
const BASE_URL = 'https://demo.motionmind.store';
const LOGIN_EMAIL = 'admin@lansiapapua.id';
const LOGIN_PASSWORD = 'admin12345678';

// Custom metrics
const errorRate = new Rate('errors');
const dashboardDuration = new Trend('dashboard_duration', true);
const lansiaDuration = new Trend('lansia_list_duration', true);
const loginDuration = new Trend('login_duration', true);

// Test options: 5 concurrent users, 2 minutes
export const options = {
    scenarios: {
        vps_load: {
            executor: 'ramping-vus',
            startVUs: 1,
            stages: [
                { duration: '10s', target: 3 },
                { duration: '20s', target: 5 },
                { duration: '40s', target: 5 },  // hold
                { duration: '10s', target: 0 },
            ],
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<5000'],
        errors: ['rate<0.15'],
        dashboard_duration: ['p(95)<10000'],
        lansia_list_duration: ['p(95)<3000'],
    },
};

// ═══════════════════════════════════════════
// HELPER: Login with CSRF
// ═══════════════════════════════════════════
function loginWithSession() {
    // Step 1: GET login page to extract CSRF token
    const loginPage = http.get(`${BASE_URL}/login`, {
        headers: { 'Accept': 'text/html' },
    });

    if (loginPage.status !== 200) {
        console.error(`Login page failed: ${loginPage.status}`);
        return false;
    }

    // Extract CSRF token
    const csrfMatch = loginPage.body.match(/name="_token"[^>]*value="([^"]+)"/);
    if (!csrfMatch) {
        // Try alternate pattern
        const altMatch = loginPage.body.match(/value="([^"]+)"[^>]*name="_token"/);
        if (!altMatch) {
            console.error('CSRF token not found in login page');
            return false;
        }
        var csrfToken = altMatch[1];
    } else {
        var csrfToken = csrfMatch[1];
    }

    // Step 2: POST login with form data
    const start = Date.now();
    const loginRes = http.post(`${BASE_URL}/login`, {
        _token: csrfToken,
        email: LOGIN_EMAIL,
        password: LOGIN_PASSWORD,
    }, {
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Accept': 'text/html',
        },
    });
    loginDuration.add(Date.now() - start);

    // Success = 302 redirect to /app OR 200 (if followed redirect)
    const success = loginRes.status === 200 || loginRes.status === 302;

    if (!success) {
        console.error(`Login POST failed: status=${loginRes.status}`);
    }

    return success;
}

// ═══════════════════════════════════════════
// MAIN TEST FLOW
// ═══════════════════════════════════════════
export default function () {

    // 1. Health check (no auth)
    group('01_health', function () {
        const res = http.get(`${BASE_URL}/health`);
        check(res, {
            'health 200': (r) => r.status === 200,
            'healthy': (r) => {
                try { return r.json('status') === 'healthy'; }
                catch (e) { return false; }
            },
        }) || errorRate.add(1);
    });

    sleep(0.5);

    // 2. Welcome page (no auth)
    group('02_welcome', function () {
        const res = http.get(`${BASE_URL}/`);
        check(res, {
            'welcome 200': (r) => r.status === 200,
            'has JALAN': (r) => r.body.includes('JALAN'),
        }) || errorRate.add(1);
    });

    sleep(0.5);

    // 3. Login
    group('03_login', function () {
        const ok = loginWithSession();
        check(null, {
            'login success': () => ok,
        }) || errorRate.add(1);
    });

    sleep(1);

    // 4. Dashboard (heaviest — 100k data)
    group('04_dashboard', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app`, {
            headers: { 'Accept': 'text/html' },
        });
        dashboardDuration.add(Date.now() - start);

        check(res, {
            'dashboard 200': (r) => r.status === 200,
            'has Total Responden': (r) => r.body.includes('Total Responden'),
        }) || errorRate.add(1);
    });

    sleep(1.5);

    // 5. Lansia list
    group('05_lansia_list', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app/lansia`, {
            headers: { 'Accept': 'text/html' },
        });
        lansiaDuration.add(Date.now() - start);

        check(res, {
            'lansia 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(1);

    // 6. Dashboard filtered (gender)
    group('06_dashboard_filtered', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app?gender=female`, {
            headers: { 'Accept': 'text/html' },
        });
        dashboardDuration.add(Date.now() - start);

        check(res, {
            'filtered 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(1);

    // 7. Lansia search
    group('07_lansia_search', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app/lansia?search=Demo`, {
            headers: { 'Accept': 'text/html' },
        });
        lansiaDuration.add(Date.now() - start);

        check(res, {
            'search 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(1);

    // 8. Village search API
    group('08_village_search', function () {
        const res = http.get(`${BASE_URL}/app/wilayah/villages/search?q=Jayapura`, {
            headers: { 'Accept': 'application/json' },
        });

        check(res, {
            'village search 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(0.5);
}
