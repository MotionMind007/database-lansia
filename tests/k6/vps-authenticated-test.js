import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// ═══════════════════════════════════════════
// KONFIGURASI
// ═══════════════════════════════════════════
const BASE_URL = 'https://demo.motionmind.store';
const LOGIN_EMAIL = 'admin@lansiapapua.id';
const LOGIN_PASSWORD = 'admin12345678';

const errorRate = new Rate('errors');
const dashboardDuration = new Trend('dashboard_duration', true);
const lansiaDuration = new Trend('lansia_list_duration', true);

export const options = {
    scenarios: {
        authenticated_load: {
            executor: 'ramping-vus',
            startVUs: 1,
            stages: [
                { duration: '10s', target: 3 },
                { duration: '30s', target: 5 },
                { duration: '30s', target: 5 },
                { duration: '10s', target: 0 },
            ],
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<3000'],
        errors: ['rate<0.1'],
        dashboard_duration: ['p(95)<2000'],
        lansia_list_duration: ['p(95)<1500'],
    },
};

// ═══════════════════════════════════════════
// SETUP: Login sekali, simpan cookies
// ═══════════════════════════════════════════
export function setup() {
    // Get CSRF
    const loginPage = http.get(`${BASE_URL}/login`);
    const csrfMatch = loginPage.body.match(/name="_token"[^>]*value="([^"]+)"/);

    if (!csrfMatch) {
        console.error('CSRF not found');
        return { loggedIn: false };
    }

    // Login
    const res = http.post(`${BASE_URL}/login`, {
        _token: csrfMatch[1],
        email: LOGIN_EMAIL,
        password: LOGIN_PASSWORD,
    }, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    });

    // Verify login worked
    const dashboard = http.get(`${BASE_URL}/app`);
    if (dashboard.status === 200 && dashboard.body.includes('Total Responden')) {
        console.log('✓ Setup login successful');
        return { loggedIn: true };
    }

    console.error('Setup login failed — tests will hit login redirects');
    return { loggedIn: false };
}

// ═══════════════════════════════════════════
// MAIN: Semua request pakai session dari setup
// ═══════════════════════════════════════════
export default function (data) {
    if (!data.loggedIn) {
        // Re-login per VU jika setup gagal
        const lp = http.get(`${BASE_URL}/login`);
        const m = lp.body.match(/name="_token"[^>]*value="([^"]+)"/);
        if (m) {
            http.post(`${BASE_URL}/login`, {
                _token: m[1],
                email: LOGIN_EMAIL,
                password: LOGIN_PASSWORD,
            });
        }
        sleep(1);
    }

    // Dashboard (cached)
    group('dashboard', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app`);
        dashboardDuration.add(Date.now() - start);

        check(res, {
            'dashboard 200': (r) => r.status === 200,
            'has stats': (r) => r.body.includes('Total Responden'),
        }) || errorRate.add(1);
    });

    sleep(1);

    // Dashboard filtered
    group('dashboard_filtered', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app?gender=male`);
        dashboardDuration.add(Date.now() - start);

        check(res, {
            'filtered 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(1);

    // Lansia list
    group('lansia_list', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app/lansia`);
        lansiaDuration.add(Date.now() - start);

        check(res, {
            'lansia 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(0.5);

    // Lansia search
    group('lansia_search', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app/lansia?search=Papua`);
        lansiaDuration.add(Date.now() - start);

        check(res, {
            'search 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(0.5);

    // Lansia page 2
    group('lansia_page2', function () {
        const start = Date.now();
        const res = http.get(`${BASE_URL}/app/lansia?page=2`);
        lansiaDuration.add(Date.now() - start);

        check(res, {
            'page2 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(0.5);

    // Village search API
    group('village_api', function () {
        const res = http.get(`${BASE_URL}/app/wilayah/villages/search?q=Jaya`);
        check(res, {
            'village 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(0.5);

    // Health (baseline)
    group('health', function () {
        const res = http.get(`${BASE_URL}/health`);
        check(res, {
            'health 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    });

    sleep(0.5);
}
