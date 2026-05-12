# ams

## Docker setup

1. Copy the example environment file to `.env`:
   ```sh
   cp .env.example .env
   ```

2. Build and start the containers:
   ```sh
   docker compose up --build
   ```

3. Open the app in your browser:
   ```sh
   http://localhost:8080
   ```

## Notes

- MySQL is exposed on port `3306`.
- SQL Server is exposed on port `1433`.
- The MySQL initialization file is loaded from `database/ams_db.sql`.
- If you change code after container creation, rebuild with:
  ```sh
  docker compose up --build
  ```
- If Composer dependencies are not already present in `vendor/`, run:
  ```sh
  docker compose run web composer install
  ```
