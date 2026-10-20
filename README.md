стек: PHP 8.3, Laravel 12, SQLite, Laravel Sanctum, Docker

## быстрый запуск через Docker

```bash
docker compose up -d --build
```

контейнер сам ставит зависимости, генерирует ключ приложения, выполняет миграции и сиды и запускает сервер

- веб-клиент (схематический для удобства): http://localhost:8000/
- базовый адрес API: http://localhost:8000/api

## запуск без Docker

требуется PHP 8.3 с расширениями `pdo_sqlite`, `mbstring`, `openssl`, `curl` и Composer

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

приложение доступно по адресу http://localhost:8000

## тестовые пользователи

сиды создают тариф с `id = 15` и двух пользователей с готовыми API-токенами.


| Alice Agent  | alice@example.com | `alice-9f2c4d7e1b6a8c3d5e7f9a1b2c4d6e8f` |
| Bob Broker   | bob@example.com   | `bob-3a7b9c1d5e2f4a6b8c0d1e3f5a7b9c2d`   |


каждый запрос к api должен содержать заголовок `Authorization: Bearer <token>`
пользователь всегда берётся из токена, а не из тела запроса

## API


| POST  | `/api/contracts/calculate` | Расчёт премии           | 200        |
| POST  | `/api/contracts/reserve`   | Резервирование договора | 201 / 200  |
| GET   | `/api/contracts/{id}`      | Получение договора      | 200        |

### Тело запроса для `calculate` и `reserve`

```json
{
  "tariff_id": 15,
  "date_start": "2026-11-01",
  "date_end": "2027-10-31",
  "insurance_sum": 100000,
  "insured_persons": [
    { "name": "Ivan", "birth_date": "1995-05-10" },
    { "name": "Olena", "birth_date": "1960-03-15" }
  ]
}
```

### POST /api/contracts/calculate

```bash
curl -X POST http://localhost:8000/api/contracts/calculate \
  -H "Authorization: Bearer alice-9f2c4d7e1b6a8c3d5e7f9a1b2c4d6e8f" \
  -H "Content-Type: application/json" \
  -d '{"tariff_id":15,"date_start":"2026-11-01","date_end":"2027-10-31","insurance_sum":100000,"insured_persons":[{"name":"Ivan","birth_date":"1995-05-10"},{"name":"Olena","birth_date":"1960-03-15"}]}'
```

Ответ `200`:

```json
{
  "data": {
    "tariff": { "id": 15, "name": "Standard life insurance", "currency": "EUR", "insurance_sum": 30000, "base_rate": 0.005 },
    "date_start": "2026-11-01",
    "date_end": "2027-10-31",
    "insurance_sum": 100000,
    "exchange_rate": { "currency": "EUR", "rate": 50.5301, "date": "2026-10-21" },
    "insured_persons": [
      { "name": "Ivan", "birth_date": "1995-05-10", "age": 31, "coefficients": { "age_coefficient": 1, "group_discount": 0.9 }, "premium": 135, "premium_uah": 6821.56 },
      { "name": "Olena", "birth_date": "1960-03-15", "age": 66, "coefficients": { "age_coefficient": 1.8, "group_discount": 0.9 }, "premium": 243, "premium_uah": 12278.81 }
    ],
    "total_premium": 378,
    "total_premium_uah": 19100.37
  }
}
```

Курс запрашивается у НБУ при каждом вызове, поэтому значения в гривне зависят от дня (хотел сделать через кеширование на день но понял что это не надо )

### POST /api/contracts/reserve

Обязателен заголовок `Idempotency-Key`.

```bash
curl -X POST http://localhost:8000/api/contracts/reserve \
  -H "Authorization: Bearer alice-9f2c4d7e1b6a8c3d5e7f9a1b2c4d6e8f" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: demo-1" \
  -d '{"tariff_id":15,"date_start":"2026-11-01","date_end":"2027-10-31","insurance_sum":100000,"insured_persons":[{"name":"Ivan","birth_date":"1995-05-10"},{"name":"Olena","birth_date":"1960-03-15"}]}'
```

ключи изолированы по пользователям: один и тот же ключ у разных пользователей это разные договоры



### Ошибки

все ошибки возвращаются в JSON

| 401 | Нет токена или токен неверный
| 403 | Договор принадлежит другому пользователю
| 404 | Договор или маршрут не найден
| 409 | Повтор `Idempotency-Key` с другими данными
| 422 | Ошибка валидации
| 503 | Курс НБУ недоступен

## Как добавить тариф или правило

Новый тариф: добавить строку в `tariffs` и нужные строки в `tariff_rules` (через сидер или напрямую в БД)
Код не меняется.

Новое правило расчёта:

1. Создать класс в `app/Services/Premium/Rules`, реализующий `PremiumRule`
   Конструктор получает параметры из `tariff_rules.parameters`, метод `coefficient()` возвращает коэффициент для персоны
2. Зарегистрировать тип в `config/premium.php`
3. Добавить строку с этим типом в `tariff_rules` нужного тарифа

Контроллер и калькулятор не меняются, правила применяются в порядке поля `priority`


## Тесты (сделаны через ИИшку)

```bash
php artisan test
```
