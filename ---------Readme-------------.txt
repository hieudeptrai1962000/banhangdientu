================ HƯỠNG DẪN CĂN BẢN ================
b1: tải về và giải nén.
b2: coppy toàn bộ vào thư mục htdocs của server.
b3: tạo csdl và inport file mshop.sql (trong thư mục database/mshop.sql).
b4: cấu hình lại file .env cho phù hợp với thiết lập của bạn(cả tài khoản mail (tạm thời sử dụng mailtrap.io bạn vào 9trang chủ tạo accound riêng và cấu hình theo họ hưỡng dẫn))
b5: (nếu thay đổi) cấu hình paypal API tại congif/paypall.php
================ tài khoản ADMIN ================
truy cập vào:
http://yoursite/admin (+yoursite : tên website của bạn)
--------/admin accound/--------------------
email: admin@gmail.com
mk: admin
------------------/pp accound/-----------------------
accound sandbox paypall:
email: gamevietsp-buyer@gmail.com
pass: pp1212123
-------------------/--/-----------------
=====tốt nhất bạn nên vào https://developer.paypal.com/ tạo cho mình một tài khoản dev paypal riêng cho mình ! ====

================/--change log/============================
- cập nhật lên phiên bản laravel 5.3 
- Multi Auth khá hoàn chình, có resetpassword
- Cập nhật thanh toán paypal
- một số tinh chỉnh khác (hoàn thành một số mục trong back-end chưa làm của bản trước, setting, ...)




