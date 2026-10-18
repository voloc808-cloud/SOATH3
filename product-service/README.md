# Bài thực hành 3 — Product Management (Lumen)

Service REST độc lập, cổng **8001**, database MySQL **product_service**. Service bài 2 giữ việc đăng nhập và xác thực; service này không dùng chung bảng users hoặc database của bài 2.

## Chạy dự án

Yêu cầu PHP >= 8.1, Composer, MySQL; bật extension `pdo_mysql`, `mbstring`, `openssl`. Kiểm tra bằng `php -m`. Trên máy hiện tại DLL có trong `C:\PHP\ext`; có thể bật trong php.ini hoặc truyền `-d extension=...` khi chạy PHP.

```powershell
cd product-service
composer install
Copy-Item .env.example .env
```

Tạo database bằng MySQL:

```sql
CREATE DATABASE product_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sửa thông tin `DB_*` và `AUTH_VERIFY_URL` trong `.env`, sau đó:

```powershell
php artisan migrate
php -S localhost:8001 -t public
```

Nếu PHP máy hiện tại chưa bật extension:

```powershell
php -d extension_dir=C:\PHP\ext -d extension=php_pdo_mysql.dll -d extension=php_mbstring.dll artisan migrate
php -d extension_dir=C:\PHP\ext -d extension=php_pdo_mysql.dll -d extension=php_mbstring.dll -S localhost:8001 -t public
```

## Nối xác thực bài 2

`AUTH_VERIFY_URL=http://localhost:8000/api/user` chỉ là URL mẫu, cần thay đúng endpoint bài 2. Middleware gửi **GET** đến URL đó, kèm `Authorization: Bearer <token>` và `Accept: application/json`.

Hợp đồng yêu cầu: token hợp lệ trả **200** với JSON object thông tin người dùng; token thiếu/sai/hết hạn trả **401**, không đủ quyền trả **403**. Endpoint xác thực không được trả HTTP 200 cho token sai. Nếu bài 2 dùng POST hoặc trả `{valid:false}` cùng mã 200, phải sửa middleware theo hợp đồng thực tế trước khi dùng. Timeout hoặc lỗi service trả 503; không cho phép bỏ qua xác thực.

Đăng nhập qua bài 2, lấy access token, rồi đặt Bearer token trong Postman. Không có endpoint tự tạo token trong service sản phẩm.

## API

Tất cả `/products` yêu cầu Bearer token. Gửi JSON cùng `Content-Type: application/json` và `Accept: application/json`.

| Method | Đường dẫn | Kết quả |
|---|---|---|
| GET | /products | 200, toàn bộ danh sách trong `data` |
| GET | /products/{id} | 200, sản phẩm trong `data` |
| POST | /products | 201, sản phẩm mới |
| PUT | /products/{id} | 200, sản phẩm cập nhật |
| DELETE | /products/{id} | 204, không có body |

Body POST/PUT:

```json
{
  "name": "Bàn phím cơ",
  "description": "Kết nối USB",
  "price": "850000.00",
  "quantity": 10
}
```

PUT yêu cầu đủ `name`, `price`, `quantity`; description có thể bỏ trống. Giá không âm, tối đa 99999999.99 và tối đa 2 chữ số thập phân. Số lượng nguyên không âm, tối đa 2147483647. Tên tối đa 255 ký tự. Trường id và timestamps do hệ thống quản lý. Giá trả JSON dạng chuỗi để giữ chính xác phần thập phân.

Lỗi: 401 chưa xác thực, 403 bị từ chối, 404 không tìm thấy, 422 dữ liệu sai, 503 service xác thực không sẵn sàng.

## Kiểm thử

```powershell
php -d extension_dir=C:\PHP\ext -d extension=php_pdo_sqlite.dll -d extension=php_mbstring.dll vendor/phpunit/phpunit/phpunit
```

Test dùng SQLite trong bộ nhớ và giả lập HTTP service bài 2: CRUD, validation, token thiếu/sai và lỗi upstream. Không thay thế việc thử tích hợp service bài 2 thật hoặc MySQL thật.

Đã chạy thành công 4 tests, 15 assertions trên PHP 8.4.25. Thư viện kiểm thử Lumen 10 phát cảnh báo deprecated trên PHP 8.4; có thể dùng PHP 8.2/8.3 cho môi trường thực hành.

Import `postman/ProductService.postman_collection.json`, đặt biến `token` từ bài 2. Chạy Create trước, collection tự ghi `productId` để dùng cho Show/Update/Delete.

Các file chính: `routes/web.php`, `app/Http/Controllers/ProductController.php`, `app/Models/Product.php`, `app/Http/Middleware/RemoteAuthenticate.php`, `database/migrations/2026_10_18_000001_create_products_table.php`.
