<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body>
    <h1>{{ config('app.name') }}</h1>

    <fieldset>
        <legend>Authentication</legend>
        <label>Bearer token <input id="token" size="60" placeholder="paste API token"></label>
    </fieldset>

    <fieldset>
        <legend>Contract payload</legend>
        <textarea id="payload" rows="14" cols="80">{
  "tariff_id": 15,
  "date_start": "2026-11-01",
  "date_end": "2027-10-31",
  "insurance_sum": 100000,
  "insured_persons": [
    { "name": "Ivan", "birth_date": "1995-05-10" },
    { "name": "Olena", "birth_date": "1960-03-15" }
  ]
}</textarea>
        <p>
            <button type="button" data-action="calculate">POST /api/contracts/calculate</button>
        </p>
        <p>
            <label>Idempotency-Key <input id="idempotency-key" size="30" placeholder="any unique string"></label>
            <button type="button" data-action="reserve">POST /api/contracts/reserve</button>
        </p>
    </fieldset>

    <fieldset>
        <legend>Get contract</legend>
        <label>Contract id <input id="contract-id" type="number" min="1" size="6"></label>
        <button type="button" data-action="show">GET /api/contracts/{id}</button>
    </fieldset>

    <fieldset>
        <legend>Response</legend>
        <p id="status"></p>
        <pre id="response"></pre>
    </fieldset>

    <script>
        const statusEl = document.getElementById('status');
        const responseEl = document.getElementById('response');

        function headers() {
            const token = document.getElementById('token').value.trim();
            const result = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
            if (token) {
                result['Authorization'] = 'Bearer ' + token;
            }
            return result;
        }

        async function send(method, url, body, extraHeaders = {}) {
            statusEl.textContent = method + ' ' + url + ' ...';
            responseEl.textContent = '';
            try {
                const response = await fetch(url, {
                    method,
                    headers: { ...headers(), ...extraHeaders },
                    body: body === undefined ? undefined : JSON.stringify(body),
                });
                const text = await response.text();
                statusEl.textContent = method + ' ' + url + ' -> HTTP ' + response.status;
                try {
                    responseEl.textContent = JSON.stringify(JSON.parse(text), null, 2);
                } catch (e) {
                    responseEl.textContent = text;
                }
            } catch (error) {
                statusEl.textContent = 'Request failed';
                responseEl.textContent = String(error);
            }
        }

        function payload() {
            return JSON.parse(document.getElementById('payload').value);
        }

        const actions = {
            calculate: () => send('POST', '/api/contracts/calculate', payload()),
            reserve: () => send('POST', '/api/contracts/reserve', payload(), {
                'Idempotency-Key': document.getElementById('idempotency-key').value.trim(),
            }),
            show: () => send('GET', '/api/contracts/' + document.getElementById('contract-id').value),
        };

        document.querySelectorAll('button[data-action]').forEach((button) => {
            button.addEventListener('click', () => {
                try {
                    actions[button.dataset.action]();
                } catch (error) {
                    statusEl.textContent = 'Invalid JSON payload';
                    responseEl.textContent = String(error);
                }
            });
        });
    </script>
</body>
</html>
