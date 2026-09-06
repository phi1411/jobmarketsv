# Hướng Dẫn Vận Hành, Sao Lưu & Bảo Mật Lưu Trữ CV (CV Storage Operations & Deployment Guide)

Tài liệu này cung cấp hướng dẫn vận hành, triển khai hạ tầng, quy trình sao lưu/phục hồi (backup & restore), ranh giới bảo mật và chính sách lưu giữ dữ liệu cho tính năng tải lên và quản lý hồ sơ CV riêng tư trong hệ thống JobMarketplaceSV.

---

## 1. Lưu Trữ CV Riêng Tư (Private CV Storage)

### 1.1. Vị trí lưu trữ trên máy chủ (Production Storage Location)
* **Thư mục mặc định:** `<project_root>/storage/app/cvs/`
* **Cấu hình biến môi trường:**
  * Vị trí lưu trữ có thể cấu hình thông qua biến môi trường `CV_STORAGE_PATH` trên máy chủ hoặc file cấu hình môi trường:
    ```env
    # Ví dụ vị trí lưu trữ trên máy chủ Production Linux:
    CV_STORAGE_PATH=/var/www/jobmarket_storage/cvs
    ```
* **Quy tắc bất biến (Invariants):**
  * Thư mục lưu trữ CV **bắt buộc phải nằm ngoài thư mục web root** (`public/`).
  * Web server (Nginx/Apache) chỉ được trỏ `DocumentRoot` / `root` vào thư mục `public/`.
  * Tuyệt đối không tạo symlink, alias hay ánh xạ URL trực tiếp từ `public/` trỏ vào `storage/` hoặc thư mục CV.

### 1.2. Phân quyền tệp tin và tiến trình runtime (Filesystem Permissions & Ownership)

#### Hành vi thực tế hiện tại của mã nguồn (Current Runtime Behavior)
* Khi khởi tạo thư mục lưu trữ nếu chưa có (`ensureDirectoryExists`), mã nguồn (`CvStorageService`) thực thi:
  ```php
  @mkdir($this->storageDir, 0755, true);
  ```
* Khi sinh viên tải lên một tệp CV mới (`store`), mã nguồn gán quyền tệp tin bằng:
  ```php
  @chmod($targetPath, 0644);
  ```
* Do đó, **mã nguồn hiện tại tạo thư mục với quyền `0755` và tệp CV mới tải lên với quyền `0644`**, không tự động áp dụng `0750` hay `0640`.

#### Kiểm tra tiền triển khai bắt buộc trên Production (Required Production Preflight)
Vì mã nguồn chưa tự động siết chặt quyền `0750`/`0640`, **quy trình triển khai Production bắt buộc phải thực hiện bước Preflight** sau khi thư mục lưu trữ tồn tại:
1. **Thiết lập quyền sở hữu (Ownership):** Gán quyền sở hữu cho tài khoản service account chạy PHP runtime (ví dụ: `www-data:www-data` trên Ubuntu/Debian hoặc `apache:apache` trên RHEL/CentOS):
   ```bash
   chown -R www-data:www-data /var/www/jobmarket_storage/cvs
   ```
2. **Siết chặt quyền thư mục lưu trữ (Directory Permission Hardening):** Đặt quyền thư mục lưu trữ CV về `0750`:
   ```bash
   chmod 0750 /var/www/jobmarket_storage/cvs
   ```
3. **Cơ chế bảo vệ bằng ranh giới duyệt thư mục (Traversal Restriction Boundary):**
   * Khi thư mục `cvs` (hoặc thư mục cha của nó) được đặt quyền `0750` (`rwxr-x---`) và thuộc sở hữu của `www-data:www-data`, nhóm `others` bị tước hoàn toàn quyền execute (`---`).
   * Ranh giới duyệt thư mục này ngăn chặn tuyệt đối các tiến trình hoặc tài khoản người dùng thông thường khác trên hệ điều hành có thể truy cập, liệt kê hoặc mở bất kỳ tệp tin nào bên trong thư mục, **ngay cả khi từng tệp tin riêng lẻ mang quyền `0644`**.

#### Đề xuất siết chặt quyền runtime trong tương lai (Future Runtime Hardening Follow-up)
* Việc thay đổi trực tiếp mã nguồn trong `CvStorageService` sang `@mkdir($this->storageDir, 0750, true)` và `@chmod($targetPath, 0640)` được xác định là một **nhiệm vụ kỹ thuật siết chặt runtime trong tương lai (future runtime hardening follow-up)**.
* Cho đến khi bản cập nhật mã nguồn đó được phát hành, việc kiểm tra và cấu hình quyền `0750` tại tầng hạ tầng/deploy như hướng dẫn ở trên là bắt buộc.

### 1.3. Bảo vệ định tuyến máy chủ Web (Web Server Routing Protection)
Mọi tệp CV lưu trữ đều được đặt tên ngẫu nhiên dạng hex đục (opaque filename: 32 ký tự hex ngẫu nhiên + đuôi `.pdf`) và chỉ được phân phối qua endpoint bảo vệ: `GET /applications/{id}/cv`.

#### Cấu hình Nginx tham khảo (Chặn truy cập trực tiếp):
```nginx
server {
    listen 443 ssl http2;
    server_name jobmarket.example.vn;
    root /var/www/jobmarket/public;
    index index.php;

    # Tuyệt đối không cho phép truy cập trực tiếp vào storage hoặc các thư mục hệ thống
    location ~* ^/(app|storage|docs|tests)/ {
        deny all;
        return 404;
    }

    # Chặn các tệp ẩn và nhạy cảm (.env, .git, v.v.)
    location ~ /\. {
        deny all;
        return 404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Cấu hình Apache tham khảo:
```apache
<VirtualHost *:443>
    ServerName jobmarket.example.vn
    DocumentRoot /var/www/jobmarket/public

    <Directory /var/www/jobmarket/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Chặn hoàn toàn nếu có yêu cầu trực tiếp ra ngoài public
    <DirectoryMatch "^/var/www/jobmarket/(app|storage|docs)">
        Require all denied
    </DirectoryMatch>
</VirtualHost>
```

---

## 2. Quy Trình Sao Lưu & Phục Hồi (Backup & Restore)

### 2.1. Đơn vị phục hồi thống nhất (Single Recoverable Unit)
* **Nguyên tắc:** Cơ sở dữ liệu MySQL (`student_profiles`, `applications`) và các tệp PDF vật lý trong `CV_STORAGE_PATH` cấu thành **một đơn vị dữ liệu phục hồi duy nhất (Single Recoverable Unit)**.
* **Rủi ro nghiêm trọng khi chỉ phục hồi một trong hai:**
  1. **Chỉ phục hồi cơ sở dữ liệu (Không phục hồi tệp PDF):**
     * Các bản ghi `applications` và `student_profiles` trỏ tới các đường dẫn tệp (`cv_storage_path`) không còn tồn tại trên đĩa vật lý.
     * Khi nhà tuyển dụng hoặc sinh viên nhấn "Xem CV", endpoint trả về lỗi `404 Not Found`, làm gián đoạn quy trình tuyển dụng và mất dữ liệu của ứng viên.
  2. **Chỉ phục hồi tệp PDF (Không phục hồi cơ sở dữ liệu):**
     * Thư mục lưu trữ chứa các tệp PDF "mồ côi" (orphan files) không gắn với bất kỳ ứng viên hay đơn ứng tuyển nào trong DB.
     * Gây lãng phí dung lượng đĩa và tiềm ẩn rủi ro về rò rỉ dữ liệu hoặc vi phạm chính sách bảo mật thông tin cá nhân.

### 2.2. Quy trình sao lưu gắn kết theo BACKUP_ID (Unambiguous Paired Backup)

#### Nguyên tắc an toàn thông tin xác thực (Credential Safety Rule)
* **Tuyệt đối không truyền mật khẩu qua cờ dòng lệnh** (`-p<password>` inline) vì mật khẩu sẽ hiển thị công khai trong danh sách tiến trình (`ps aux`), shell history và log hệ thống.
* Sử dụng file cấu hình bảo mật `--defaults-extra-file` (được bảo vệ quyền `0600`, chỉ root/backup user đọc được) hoặc nhập mật khẩu tương tác qua prompt an toàn (`-p`).

#### Cấu hình file chứng thực an toàn mẫu (`/etc/mysql/backup.cnf`):
```ini
# chmod 0600 /etc/mysql/backup.cnf
[client]
user = backup_user
password = "your_strong_backup_password_here"
host = localhost
```

#### Kịch bản sao lưu đồng bộ:
Toàn bộ quy trình sao lưu sử dụng một **`BACKUP_ID` duy nhất** và lưu trữ thành một thư mục release chứa kèm tệp manifest xác thực:

```bash
#!/usr/bin/env bash
set -euo pipefail

# 1. Khởi tạo định danh sao lưu duy nhất
BACKUP_ID="jobmarket_backup_$(date +%Y%m%d_%H%M%S)"
RELEASE_DIR="/backup/releases/${BACKUP_ID}"
mkdir -p "${RELEASE_DIR}"

DB_NAME="jobmarket"
CV_DIR="/var/www/jobmarket_storage/cvs"
DB_DUMP_FILE="${RELEASE_DIR}/database.sql"
CV_ARCHIVE_FILE="${RELEASE_DIR}/cv_files.tar.gz"
MANIFEST_FILE="${RELEASE_DIR}/manifest.json"
MYSQL_CNF="/etc/mysql/backup.cnf"

echo "==> Bắt đầu sao lưu phiên bản: ${BACKUP_ID}"

# 2. Dump cơ sở dữ liệu MySQL (Sử dụng --defaults-extra-file để bảo mật credential)
# Lưu ý: Không dùng --add-drop-table để tránh lệnh DROP phá hủy khi restore
mysqldump --defaults-extra-file="${MYSQL_CNF}" \
  --single-transaction --quick --routines --triggers \
  "${DB_NAME}" > "${DB_DUMP_FILE}"

# 3. Nén thư mục lưu trữ CV
tar -czf "${CV_ARCHIVE_FILE}" -C "${CV_DIR}" .

# 4. Tính toán mã băm kiểm tra (SHA-256 Checksums)
DB_SHA256=$(sha256sum "${DB_DUMP_FILE}" | awk '{print $1}')
CV_SHA256=$(sha256sum "${CV_ARCHIVE_FILE}" | awk '{print $1}')
CV_COUNT=$(find "${CV_DIR}" -type f | wc -l)

# 5. Ghi tệp Manifest liên kết chặt chẽ hai thành phần
cat <<EOF > "${MANIFEST_FILE}"
{
  "backup_id": "${BACKUP_ID}",
  "created_at": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "database_name": "${DB_NAME}",
  "cv_storage_path": "${CV_DIR}",
  "artifacts": {
    "database": {
      "filename": "database.sql",
      "sha256": "${DB_SHA256}"
    },
    "cv_storage": {
      "filename": "cv_files.tar.gz",
      "sha256": "${CV_SHA256}",
      "file_count": ${CV_COUNT}
    }
  }
}
EOF

echo "==> Sao lưu thành công tại: ${RELEASE_DIR}"
echo "==> Tệp manifest đã được tạo: ${MANIFEST_FILE}"
```

### 2.3. Thứ tự phục hồi an toàn và có thể đảo ngược (Safe & Reversible Restore Order)

#### Yêu cầu chặn lưu lượng trước khi phục hồi (Traffic Block / Maintenance Precondition)
* Ứng dụng **không có cơ chế cờ tệp nội bộ** (như `maintenance.flag`).
* Đội ngũ vận hành **bắt buộc phải kích hoạt cơ chế chặn lưu lượng thực tế ở tầng hạ tầng** (Web Server / Load Balancer / Reverse Proxy) để trả về mã HTTP `503 Service Unavailable` trước khi tiến hành sao lưu hoặc phục hồi:
  * **Trên Nginx:** Bật cấu hình trả về 503 cho toàn bộ mutation traffic (`return 503;`).
  * **Trên Load Balancer (AWS ALB, Cloudflare, HAProxy):** Chuyển listener rule sang Maintenance Mode page.
* **Mục đích:** Ngăn chặn hoàn toàn việc người dùng tiếp tục upload CV (`POST /student/cv`) hoặc gửi đơn ứng tuyển (`POST /jobs/{id}/applications`) trong khi dữ liệu đang được phục hồi, loại bỏ rủi ro sai lệch dữ liệu giữa database và filesystem.

#### Quy trình phục hồi an toàn (Reversible & Non-Destructive):
Tuyệt đối **không xóa vĩnh viễn dữ liệu hiện tại** và **không sử dụng lệnh `DROP DATABASE`** phá hủy. Thay vào đó, áp dụng quy trình phục hồi vào database tạm thời rồi kiểm tra trước khi chuyển đổi (cut-over):

1. **Bước 1 — Kích hoạt bảo trì ở tầng Web Server / Load Balancer:**
   * Cấu hình Nginx / Reverse Proxy trả về HTTP 503 cho người dùng.
   * Xác nhận không còn kết nối ghi dữ liệu đang hoạt động vào MySQL và thư mục lưu trữ CV.

2. **Bước 2 — Phục hồi Filesystem một cách có thể đảo ngược (Reversible Filesystem Restore):**
   * Chỉ định `BACKUP_ID` cần phục hồi (ví dụ: `jobmarket_backup_20260906_150000`).
   * Kiểm tra tính toàn vẹn của tệp nén CV qua manifest:
     ```bash
     cd "/backup/releases/${BACKUP_ID}"
     sha256sum -c <(echo "$(jq -r '.artifacts.cv_storage.sha256' manifest.json)  cv_files.tar.gz")
     ```
   * **Di chuyển an toàn dữ liệu CV hiện tại (Reversible Move)** sang thư mục dự phòng có timestamp:
     ```bash
     PRE_RESTORE_ID="pre_restore_$(date +%Y%m%d_%H%M%S)"
     if [ -d "/var/www/jobmarket_storage/cvs" ]; then
         mv /var/www/jobmarket_storage/cvs "/var/www/jobmarket_storage/cvs_backup_${PRE_RESTORE_ID}"
     fi
     mkdir -p /var/www/jobmarket_storage/cvs
     ```
   * Giải nén tệp CV từ đúng bản sao lưu của `BACKUP_ID`:
     ```bash
     tar -xzf "/backup/releases/${BACKUP_ID}/cv_files.tar.gz" -C /var/www/jobmarket_storage/cvs/
     chown -R www-data:www-data /var/www/jobmarket_storage/cvs/
     chmod 0750 /var/www/jobmarket_storage/cvs/
     ```

3. **Bước 3 — Phục hồi Database vào cơ sở dữ liệu tạm thời (Clean Temporary Database Restore):**
   * Kiểm tra tính toàn vẹn của tệp dump SQL qua manifest:
     ```bash
     sha256sum -c <(echo "$(jq -r '.artifacts.database.sha256' manifest.json)  database.sql")
     ```
   * *Lưu ý quan trọng:* Tệp dump không chứa `--add-drop-table`, do đó nạp trực tiếp vào database hiện tại sẽ bị lỗi va chạm bảng (`Table already exists`).
   * Tạo một database rỗng mới dùng riêng cho việc phục hồi:
     ```bash
     RESTORE_DB="jobmarket_restore_${BACKUP_ID}"
     mysql --defaults-extra-file=/etc/mysql/backup.cnf \
       -e "CREATE DATABASE IF NOT EXISTS \`${RESTORE_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
     ```
   * Nạp dữ liệu SQL dump vào database rỗng này:
     ```bash
     mysql --defaults-extra-file=/etc/mysql/backup.cnf "${RESTORE_DB}" < "/backup/releases/${BACKUP_ID}/database.sql"
     ```

4. **Bước 4 — Kiểm tra đối soát sau phục hồi (Post-Restore Verification):**
   * Chạy các truy vấn đối soát giữa `RESTORE_DB` và các tệp trong thư mục `/var/www/jobmarket_storage/cvs` (xem mục 2.4).
   * Đảm bảo mọi tệp CV tham chiếu đều tồn tại trên đĩa và khớp kích thước.
   * Nếu phát hiện lỗi: Hoàn tác filesystem bằng cách khôi phục thư mục `cvs_backup_${PRE_RESTORE_ID}` và hủy database tạm `RESTORE_DB`.

5. **Bước 5 — Chuyển đổi cấu hình có kiểm soát (Controlled Configuration Cut-over):**
   * Sau khi kiểm tra thành công 100%, cập nhật cấu hình ứng dụng (`DB_NAME` trong `.env`) trỏ sang database mới đã phục hồi (`RESTORE_DB`), hoặc thực hiện hoán đổi database / đổi tên bảng trong cửa sổ bảo trì.
   * Giữ nguyên database cũ để phục vụ rollback nếu cần thiết.

6. **Bước 6 — Tắt chế độ bảo trì & Phục hồi dịch vụ (Resume Service):**
   * Tắt phản hồi 503 trên Nginx / Load Balancer, mở lại lưu lượng cho người dùng.

### 2.4. Đối soát sau phục hồi (Post-Restore Verification Steps)
Thực hiện truy vấn đối soát để đảm bảo không có bản ghi nào bị mồ côi hoặc trỏ tới tệp tin không tồn tại:

```sql
-- Liệt kê danh sách các tệp CV snapshot cần kiểm tra sự tồn tại trên đĩa
SELECT id, user_id, cv_storage_path, cv_original_name, cv_file_size 
FROM applications 
WHERE cv_storage_path IS NOT NULL;

-- Liệt kê danh sách các CV hoạt động trong hồ sơ sinh viên
SELECT id, user_id, cv_storage_path, cv_original_name, cv_file_size 
FROM student_profiles 
WHERE cv_storage_path IS NOT NULL;
```
Trên máy chủ, chạy script kiểm tra đối chiếu danh sách `cv_storage_path` thu được với tệp vật lý thực tế trong `CV_STORAGE_PATH`:
* Tất cả tệp vật lý tham chiếu bởi `applications` phải tồn tại và đọc được.
* Kích thước tệp trên đĩa khớp với `cv_file_size` trong database.
* Không có sự chênh lệch (mismatch) giữa bản ghi DB và tệp vật lý.

---

## 3. Chính Sách Lưu Giữ & Xóa Dữ Liệu (Retention Policy)

### 3.1. Quyết định chính sách sản phẩm & pháp lý đang chờ phê duyệt (Pending Product/Legal Decision)
* **Thực trạng hiện tại:**
  * Việc lưu giữ hay xóa bỏ các tệp CV đính kèm khi **sinh viên rút đơn ứng tuyển (`status = 'withdrawn'`)** hoặc khi **người dùng yêu cầu xóa tài khoản (Account Deletion / Right to be Forgotten)** hiện **chưa có quyết định pháp lý và quy định sản phẩm chính thức**.
  * Cần cân nhắc giữa:
    * **Quyền riêng tư của sinh viên:** Tuân thủ các quy định về bảo vệ dữ liệu cá nhân (Nghị định 13/2023/NĐ-CP của Việt Nam) về việc hủy dữ liệu khi rút lại sự đồng ý.
    * **Quyền lợi và nghĩa vụ kiểm toán của doanh nghiệp tuyển dụng:** Doanh nghiệp có thể cần lưu vết hồ sơ để giải trình khiếu nại tuyển dụng, tranh chấp lao động hoặc kiểm toán quy trình tuyển dụng trong một khoảng thời gian nhất định (ví dụ: 6 tháng đến 1 năm).

### 3.2. Ranh giới kỹ thuật hiện tại (Architectural Boundaries)
* **Tuyệt đối không tự ý xóa tự động (Do NOT implement automated deletion):**
  * Trong giai đoạn này, hệ thống **không triển khai bất kỳ cron job, trigger hay tiến trình nền nào tự động xóa tệp CV** của các đơn đã rút hoặc tài khoản xóa.
  * Các bản ghi `applications` và tệp CV snapshot vật lý tương ứng được giữ **bất biến (immutable)**.
  * Khi bộ phận Sản phẩm (Product Team) và Pháp chế (Legal Team) ban hành văn bản quy định chính thức về thời gian lưu giữ (Data Retention Schedule), đội ngũ phát triển sẽ lập kế hoạch xây dựng module lưu trữ/thanh lọc (Archival & Purging Module) có nhật ký kiểm toán (Audit Trail) đầy đủ.

---

## 4. An Toàn Vận Hành & Rà Soát Mã Độc (Security Operations & Malware Scanning)

### 4.1. Ranh giới an toàn nhật ký (Safe Logging Boundaries)
Để bảo vệ quyền riêng tư của người dùng và ngăn chặn rò rỉ cấu trúc máy chủ:
* **Không ghi nội dung tệp tin vào log:** Tuyệt đối không ghi stream dữ liệu, nội dung trích xuất thô, hay thông tin cá nhân trong CV vào file log ứng dụng (`app.log`, access log).
* **Không để lộ đường dẫn tệp riêng tư:**
  * Đường dẫn tệp tin nội bộ đầy đủ trên máy chủ (ví dụ: `/var/www/jobmarket_storage/cvs/abcdef123456.pdf`) **tuyệt đối không được trả về trong JSON response** cho client và không được hiển thị trong thông báo lỗi trên UI.
  * Trong log ứng dụng, chỉ ghi nhận mã băm, UUID của application, hoặc tên tệp gốc an toàn (`cv_original_name`), không in đường dẫn tuyệt đối của hệ điều hành.

### 4.2. Kế hoạch quét mã độc khi quy mô tăng trưởng (Malware Scanning Roadmap)
* **Kiểm tra hiện tại ở tầng ứng dụng (Application-level Validation):**
  * Đã kiểm tra định dạng PDF thông qua cấu trúc Magic Bytes `%PDF-`.
  * Đã xác thực MIME type thực tế bằng `finfo`/`mime_content_type` (`application/pdf`).
  * Đã chặn tệp có dung lượng vượt quá giới hạn 5 MB.
* **Tích hợp rà soát mã độc chuyên sâu khi lượng upload tăng cao:**
  * Khi số lượng hồ sơ ứng tuyển mở rộng trong môi trường Production, hệ thống cần bổ sung một lớp quét mã độc tự động (Antivirus Pipeline) trước khi tệp được đưa vào trạng thái sử dụng:
    1. **Tích hợp ClamAV Daemon (`clamd`):**
       * Tệp sau khi upload tạm thời được lưu trong thư mục cách ly (`quarantine`).
       * PHP gọi socket ClamAV qua lệnh `INSTREAM` hoặc `SCAN` để phát hiện tệp PDF chứa mã khai thác PDF (như embedded JavaScript độc hại, buffer overflow exploit).
    2. **Chế độ xử lý khi phát hiện vi phạm:**
       * Nếu tệp bị nhiễm mã độc: Lập tức hủy tệp, không ghi nhận vào `student_profiles`, ghi log bảo mật (Security Alert Log) gửi tới Quản trị viên, và thông báo lỗi an toàn cho người dùng: *"Tệp tin không vượt qua bài kiểm tra an toàn bảo mật."*
    3. **Quét bất đồng bộ (Queue Worker):**
       * Với khối lượng tệp lớn, chuyển tác vụ quét sang hàng đợi bất đồng bộ (Background Worker) để không làm nghẽn luồng xử lý HTTP request của người dùng.
