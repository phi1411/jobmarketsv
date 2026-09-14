# Location Backend — JobMarketSV

## Quyết định kiến trúc

- `company.address` chỉ là địa chỉ doanh nghiệp; địa điểm làm việc nằm trong `job_locations`.
- Một job có tối đa 20 địa điểm. Một địa điểm được đánh dấu `is_primary`.
- Địa chỉ chuẩn gồm `address_text`, `province`, `commune`, `district_text_legacy`, `latitude`, `longitude`.
- `district_text_legacy` chỉ phục vụ dữ liệu/tên gọi cũ. Bộ lọc chính dùng `province` và `commune`, phù hợp mô hình hành chính Việt Nam từ 01/07/2025.
- Tọa độ hiện tại từ browser chỉ dùng trong request tìm kiếm/kiểm tra quãng đường, không lưu DB.
- API key Goong REST chỉ tồn tại ở backend. UI tuyệt đối không nhận hoặc nhúng key này.
- Tìm gần dùng Haversine + bounding box trong MySQL. Distance Matrix chỉ được gọi khi cần quãng đường theo đường đi.

## Biến môi trường

```dotenv
GOONG_REST_API_KEY=
GOONG_MAPTILES_KEY=
GOONG_API_BASE_URL=https://rsapi.goong.io
GOONG_TIMEOUT_SECONDS=8
LOCATION_API_RATE_LIMIT=60
```

`GOONG_MAPTILES_KEY` là key khác với REST key. Trang chi tiết việc làm dùng key này để nhúng bản đồ Goong; không dùng REST key ở trình duyệt.

## API cho ô chọn địa chỉ

Các API proxy Goong bên dưới yêu cầu đăng nhập và được rate-limit theo user/IP.

### Gợi ý địa chỉ

`GET /map/places/autocomplete?input=nguyen%20hue&latitude=10.7769&longitude=106.7009&limit=8&radius_km=20&session_token=<uuid>`

Response item:

```json
{
  "place_id": "...",
  "description": "Nguyễn Huệ, Phường Bến Nghé, Quận 1, Thành phố Hồ Chí Minh",
  "province": "Hồ Chí Minh",
  "commune": "Bến Nghé",
  "district_text_legacy": "Quận 1"
}
```

UI nên tạo một `session_token` cho mỗi phiên gõ/chọn địa chỉ và dùng lại token đó ở Place Detail.

### Lấy chi tiết địa điểm đã chọn

`POST /map/places/detail`

```json
{
  "place_id": "place-id-from-autocomplete",
  "session_token": "same-session-token"
}
```

Backend trả `address_text`, các cấp hành chính và tọa độ. UI không cần tin vào tọa độ do client tự gửi khi người dùng chọn kết quả Goong.

### Geocode / Reverse geocode

- `POST /map/geocode` — `{ "address": "..." }`
- `POST /map/reverse-geocode` — `{ "latitude": 10.77, "longitude": 106.70 }`

## Địa điểm làm việc của job

### Danh sách công khai

`GET /jobs/{job_id}/locations`

### Công ty thêm địa điểm

`POST /company/jobs/{job_id}/locations`

```json
{
  "place_id": "place-id-from-autocomplete",
  "session_token": "same-session-token",
  "branch_name": "Chi nhánh Nguyễn Huệ",
  "is_primary": true
}
```

Backend tự gọi Place Detail và lưu dữ liệu đã xác thực. Cũng hỗ trợ nhập thủ công bằng `address_text`, `latitude`, `longitude`, nhưng bản ghi sẽ có `geocode_status=manual`.

### Sửa / xóa

- `PATCH /company/jobs/{job_id}/locations/{location_id}`
- `DELETE /company/jobs/{job_id}/locations/{location_id}`

Chỉ công ty sở hữu job được thao tác. Khi xóa địa điểm chính, backend tự chọn địa điểm còn lại làm địa điểm chính.

## Tìm việc gần tôi

`POST /jobs/nearby-search` là API công khai.

```json
{
  "latitude": 10.7769,
  "longitude": 106.7009,
  "radius_km": 10,
  "work_mode": "onsite",
  "work_type": "part_time",
  "category_id": "optional",
  "page": 1,
  "per_page": 12
}
```

`radius_km` nhận `2`, `5`, `10`, `20`, `50`. Kết quả được sắp từ gần nhất, có:

```json
{
  "distance_km": 2.31,
  "nearest_location": { "address_text": "...", "latitude": 0, "longitude": 0 },
  "work_locations": []
}
```

Meta trả `origin`, `radius_km`, `distance_type=straight_line`. Backend không ghi origin xuống database.

## Cảnh báo quãng đường trước khi apply

`POST /jobs/{job_id}/commute-check`

Nhanh, không gọi Goong:

```json
{
  "latitude": 10.80,
  "longitude": 106.71,
  "max_commute_km": 15,
  "use_route": false
}
```

Chính xác theo đường đi, có gọi Goong Distance Matrix:

```json
{
  "latitude": 10.80,
  "longitude": 106.71,
  "max_commute_km": 15,
  "use_route": true,
  "vehicle": "bike"
}
```

Response có `is_far` và `warning`. Đây là cảnh báo mềm; UI vẫn cho phép ứng tuyển.

## Khu vực mong muốn của sinh viên

- `GET /student/preferred-locations`
- `PUT /student/preferred-locations`

```json
{
  "locations": [
    {
      "place_id": "...",
      "session_token": "...",
      "preferred_radius_km": 10
    },
    {
      "place_id": "...",
      "session_token": "...",
      "preferred_radius_km": 5
    }
  ]
}
```

Tối đa 10 khu vực. Gửi mảng rỗng để xóa toàn bộ lựa chọn.

## Tương thích dữ liệu cũ

Migration tạo một `job_locations` dạng `legacy_pending` cho job cũ có địa chỉ nhưng chưa có tọa độ. Những bản ghi đó vẫn hiển thị, nhưng chưa tham gia tìm kiếm theo bán kính cho tới khi công ty chọn lại địa chỉ qua Goong hoặc nhập tọa độ thủ công.

Có thể chuẩn hóa dữ liệu cũ theo từng lô bằng lệnh sau. Chạy không có `--execute` để xem trước số lượng trước khi gọi API:

```bash
php backfill_job_locations.php --limit=25
php backfill_job_locations.php --execute --limit=25
```

## Nguồn kỹ thuật

1. Goong, [Place API — AutoComplete và Detail](https://docs.goong.io/rest/place/).
2. Goong, [Geocoding API](https://docs.goong.io/rest/geocode/).
3. Goong, [Distance Matrix API](https://docs.goong.io/rest/distance_matrix/).
4. Goong, [API keys — REST key và Map tiles key](https://docs.goong.io/rest/api-key/).
5. Cổng TTĐT Chính phủ, [Danh mục 34 đơn vị hành chính cấp tỉnh và 3.321 đơn vị cấp xã từ 01/07/2025](https://xaydungchinhsach.chinhphu.vn/bang-danh-muc-va-ma-so-cua-34-tinh-thanh-moi-cac-don-vi-hanh-chinh-cap-xa-moi-11925070418263625.htm).
