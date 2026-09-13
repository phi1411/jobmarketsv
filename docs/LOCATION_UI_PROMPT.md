# Prompt giao Anti làm UI Location

Hãy thiết kế và triển khai giao diện location cho website tuyển dụng sinh viên JobMarketSV dựa đúng vào backend API đã có. Chỉ làm frontend/UI, không thay đổi schema, không tự gọi Goong trực tiếp và tuyệt đối không đưa `GOONG_REST_API_KEY` vào browser.

## Mục tiêu trải nghiệm

Giao diện mang cảm giác sản phẩm tuyển dụng hoàn chỉnh như TopCV nhưng giữ nhận diện hiện tại của dự án. Ưu tiên mobile-first, rõ ràng, dễ dùng cho sinh viên và nhà tuyển dụng. Không hard-code cây Quận/Huyện cũ làm dữ liệu chính; hiển thị theo `province` + `commune`, còn `district_text_legacy` chỉ là nhãn phụ nếu backend trả về.

## 1. Component AddressAutocomplete dùng chung

- Input có label, placeholder, loading, empty state, lỗi mạng và nút xóa.
- Khi người dùng gõ từ 2 ký tự, debounce 300–400 ms rồi gọi:
  `GET /map/places/autocomplete?input=...&latitude=...&longitude=...&limit=8&radius_km=50&session_token=...`
- Tạo UUID `session_token` khi focus/bắt đầu một phiên tìm kiếm. Dùng cùng token khi gửi place được chọn.
- Dropdown hiển thị `description`; dòng phụ ưu tiên `commune`, `province`, và có thể thêm `district_text_legacy`.
- Khi chọn kết quả, lưu `place_id`, `description`, `session_token`. Không cho người dùng sửa text rồi vẫn giữ `place_id` cũ; nếu text đổi thì xóa lựa chọn cũ.
- Hỗ trợ bàn phím: ArrowUp/ArrowDown, Enter, Escape; có ARIA combobox/listbox đúng chuẩn.
- Không gọi API ở mỗi ký tự khi query chưa đủ dài; hủy request cũ bằng AbortController.

## 2. Form đăng/sửa job — nhiều địa điểm

Tạo section “Địa điểm làm việc” tách hẳn khỏi “Địa chỉ công ty”. Mỗi location là một card gồm:

- Tên chi nhánh (không bắt buộc), ví dụ “Chi nhánh Nguyễn Huệ”.
- AddressAutocomplete.
- Radio hoặc action “Đặt làm địa điểm chính”.
- Badge “Địa điểm chính”, “Đã xác thực” hoặc “Nhập thủ công” dựa trên dữ liệu backend.
- Nút sửa/xóa; xác nhận nhẹ trước khi xóa.
- Nút “+ Thêm địa điểm”, tối đa 20 địa điểm.

Luồng API:

- Sau khi job tồn tại, thêm bằng `POST /company/jobs/{job_id}/locations` với `place_id`, `session_token`, `branch_name`, `is_primary`.
- Tải danh sách bằng `GET /jobs/{job_id}/locations`.
- Sửa bằng `PATCH /company/jobs/{job_id}/locations/{location_id}`.
- Xóa bằng `DELETE /company/jobs/{job_id}/locations/{location_id}`.

Sau mỗi mutation, render response backend làm source of truth; không tự dựng lại province/commune/lat/lng ở client.

## 3. Bộ lọc “Việc làm gần tôi”

Thêm CTA nổi bật `[Việc làm gần tôi]` ở trang danh sách job.

- Chỉ hỏi quyền vị trí sau khi người dùng chủ động bấm nút, không hỏi ngay khi tải trang.
- Giải thích ngắn: “Vị trí chỉ dùng cho lần tìm kiếm này và không được lưu.”
- Nếu đồng ý, dùng `navigator.geolocation`, rồi gọi `POST /jobs/nearby-search`.
- Chip bán kính: `2 km`, `5 km`, `10 km`, `20 km`; mặc định `10 km`.
- Có sort label “Gần nhất” (backend đã trả đúng thứ tự).
- Xử lý đủ các trạng thái: đang xin quyền, bị từ chối, timeout, vị trí không khả dụng, không có kết quả, retry.
- Nếu từ chối GPS, đề nghị người dùng chọn khu vực mong muốn thay thế; không khóa trang.

Payload mẫu:

```json
{
  "latitude": 10.7769,
  "longitude": 106.7009,
  "radius_km": 10,
  "work_mode": "onsite",
  "page": 1,
  "per_page": 12
}
```

## 4. Job card và Job Detail

Job card khi có dữ liệu nearby:

- Hiển thị location gần nhất, ví dụ `Bến Nghé, Hồ Chí Minh`.
- Dòng nhấn vừa phải: `Cách bạn 2,3 km`.
- Nếu job có nhiều nơi, hiển thị `+3 địa điểm khác`.

Job Detail:

- Section “Địa điểm làm việc” liệt kê mọi chi nhánh, đánh dấu địa điểm chính.
- Có nút “Xem trên bản đồ”. Nếu chưa có `GOONG_MAPTILES_KEY`, dùng link điều hướng ngoài hoặc placeholder rõ ràng; không tái sử dụng REST key.
- Khi đã có MapTiles key, render marker từ `latitude`/`longitude`, fit bounds nếu nhiều điểm.

## 5. Cảnh báo trước khi ứng tuyển

Ngay trước bước xác nhận apply, nếu đã có tọa độ hiện tại, gọi:
`POST /jobs/{job_id}/commute-check`

Mặc định gọi nhanh với `use_route=false`. Chỉ khi người dùng bấm “Xem quãng đường thực tế” mới gọi lại `use_route=true`, `vehicle=bike`.

Nếu response `is_far=true`, hiển thị warning màu vàng:
“Công việc này cách khu vực của bạn khoảng 18 km.”

Giữ nút “Vẫn ứng tuyển”; đây không phải lỗi chặn.

## 6. Khu vực mong muốn trong hồ sơ sinh viên

- Multi-select tối đa 10 khu vực bằng AddressAutocomplete.
- Mỗi item có bán kính mong muốn `2/5/10/20/50 km` và nút xóa.
- Tải bằng `GET /student/preferred-locations`.
- Lưu toàn bộ bằng `PUT /student/preferred-locations` với `{ "locations": [...] }`.
- Hiển thị chip dễ quét, ví dụ `Bến Nghé · 10 km`.

## 7. Quy tắc kỹ thuật và hoàn thiện

- Tái sử dụng hệ thống màu, typography, spacing, button, toast, modal hiện có.
- Mọi request gửi cookie/token theo cơ chế API hiện tại.
- Hiển thị message lỗi do backend trả về; riêng 429 đọc `retry_after_seconds` và khóa tạm input/nút.
- Không lưu tọa độ GPS hiện tại vào localStorage, sessionStorage, analytics hoặc log. Chỉ giữ trong state của phiên trang.
- Không phơi `provider_place_id` dài trên giao diện.
- Format khoảng cách dùng dấu phẩy theo `vi-VN`, tối đa một chữ số thập phân trên card.
- Có skeleton, focus state, responsive, dark mode nếu dự án đã hỗ trợ.
- Không dùng dữ liệu giả sau khi nối API; mock chỉ được dùng trong Storybook/test.

## Tiêu chí nghiệm thu

1. Công ty thêm/sửa/xóa nhiều địa điểm và đặt địa điểm chính được.
2. Địa chỉ công ty và địa điểm job được hiển thị/tải độc lập.
3. Sinh viên tìm job trong 2/5/10/20 km và thấy khoảng cách trên card.
4. Từ chối quyền GPS vẫn sử dụng website bình thường.
5. Apply job xa chỉ cảnh báo, không chặn.
6. Sinh viên lưu được nhiều khu vực mong muốn.
7. REST API key không xuất hiện trong source frontend, network query từ browser hoặc bundle build.
