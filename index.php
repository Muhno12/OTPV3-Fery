<?php
// Jika metode POST, proses permintaan (fetch)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Baca data JSON dari body
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    // Pastikan phone dan uuid ada
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $uuid  = isset($input['uuid']) ? trim($input['uuid']) : '';
    // otp bersifat opsional
    $otp   = isset($input['otp']) ? trim($input['otp']) : '';

    if (empty($phone) || empty($uuid)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'phone dan uuid wajib disediakan.'
        ]);
        exit;
    }

    // Siapkan payload: jika otp ada, sertakan; jika tidak, hanya phone dan uuid.
    $payload = ($otp === '') 
              ? ['phone' => $phone, 'uuid' => $uuid]
              : ['phone' => $phone, 'otp' => $otp, 'uuid' => $uuid];

    // Inisialisasi CURL untuk mengirim POST ke API https://apii.biz.id/api/otp
    $ch = curl_init('https://apii.biz.id/api/otp');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        echo json_encode([
            'status' => 'error',
            'message' => $error_msg
        ]);
        exit;
    }
    curl_close($ch);
    echo $result;
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login OTP Buyer</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"/>
  <style>
    /* Global Styles */
    * { box-sizing: border-box; }
    body {
      font-family: Arial, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #1976D2, #1976D2, #1976D2);
      animation: gradient 10s ease infinite;
      margin: 0;
      padding: 0;
    }
    @keyframes gradient {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    .container {
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      max-width: 400px;
      width: 100%;
      text-align: center;
    }
    h2 { font-size: 28px; margin-bottom: 10px; color: #333; }
    h4 { font-size: 16px; margin-bottom: 20px; color: #666; }
    input {
      width: 100%;
      padding: 12px;
      font-size: 16px;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-bottom: 10px;
      transition: border-color 0.3s;
    }
    input:focus { border-color: #667eea; outline: none; }
    button {
      width: 100%;
      padding: 12px;
      font-size: 16px;
      background-color: #667eea;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
      margin-bottom: 10px;
    }
    button:hover { background-color: #556cd6; }
    button:disabled { background-color: #ccc; cursor: not-allowed; }
  </style>
</head>
<body>
  <div class="container">
    <h2>LOGIN OTP BUYER</h2>
    <h4>SOLUSI CEPAT TANPA ANTRE PROSES OTP KE SELLER</h4>
    <form id="otpForm">
      <input type="tel" id="phone" name="phone" placeholder="Masukkan Nomor Telepon" required>
      <button type="button" id="btnRequestOTP" onclick="requestOTP()">Kirim OTP</button>
      <input type="text" id="otp" name="otp" placeholder="Masukkan OTP" required disabled>
      <button type="button" id="btnVerifyOTP" onclick="verifyOTP()" disabled>Verifikasi OTP</button>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script>
    // Fungsi generateUUID menggunakan crypto API
    function generateUUID() {
      return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
        (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
      );
    }
    
    // Setiap sesi halaman mendapatkan UUID random yang unik
    const uuid = generateUUID();

    // Fungsi formatPhone: jika nomor diawali "08", ubah menjadi "628"
    function formatPhone(phone) {
      return phone.trim().replace(/^08/, "628");
    }

    let isRequestOTPInProgress = false;
    let isVerifyOTPInProgress = false;
    // Panggil endpoint index.php itu sendiri
    const apiURL = "index.php";

    function requestOTP() {
      const phoneInput = document.getElementById('phone');
      let phone = phoneInput.value.trim();
      
      if (!phone) {
        Swal.fire("Error", "Silakan isi nomor telepon terlebih dahulu", "error");
        return;
      }
      
      let formattedPhone = formatPhone(phone);
      phoneInput.value = formattedPhone;
      
      if (!formattedPhone.startsWith("628")) {
        Swal.fire("Error", "Nomor telepon harus diawali dengan 628", "error");
        return;
      }
      
      if (isRequestOTPInProgress) return;
      isRequestOTPInProgress = true;
      
      const requestBtn = document.getElementById('btnRequestOTP');
      requestBtn.disabled = true;
      requestBtn.innerHTML = "Memproses...";
      
      const payload = {
        phone: formattedPhone,
        uuid: uuid
      };
      
      fetch(apiURL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          Swal.fire('OTP Dikirim', 'OTP berhasil dikirim', 'success');
          document.getElementById('otp').disabled = false;
          document.getElementById('btnVerifyOTP').disabled = false;
          // Pertahankan nomor telepon di input agar tidak dihapus
          phoneInput.disabled = true;
        } else if (data.status === 'error') {
          Swal.fire('Error', data.message || 'Gagal mengirim OTP', 'error');
        } else {
          Swal.fire('Error', 'Respon tidak dikenali', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Terjadi kesalahan saat request OTP', 'error');
      })
      .finally(() => {
        isRequestOTPInProgress = false;
        requestBtn.disabled = false;
        requestBtn.innerHTML = "Kirim OTP";
      });
    }

    function verifyOTP() {
      const otpInput = document.getElementById('otp');
      let otp = otpInput.value.trim();

      if (!otp) {
        Swal.fire("Error", "Silakan isi OTP terlebih dahulu", "error");
        return;
      }

      if (isVerifyOTPInProgress) return;
      isVerifyOTPInProgress = true;

      const verifyBtn = document.getElementById('btnVerifyOTP');
      verifyBtn.disabled = true;
      verifyBtn.innerHTML = "Memverifikasi...";

      const phone = formatPhone(document.getElementById('phone').value.trim());

      const payload = {
        phone: phone,
        otp: otp,
        uuid: uuid
      };

      // Delay jika diperlukan; disini langsung tanpa delay
      fetch(apiURL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          Swal.fire({
            title: 'Berhasil Login',
            text: data.message,
            icon: 'success',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
          });
        } else if (data.status === 'error') {
          Swal.fire('Gagal Login', data.message || 'Gagal verifikasi OTP', 'error');
        } else {
          Swal.fire('Gagal Login', 'Respon tidak dikenali', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Terjadi kesalahan saat verifikasi OTP', 'error');
      })
      .finally(() => {
        isVerifyOTPInProgress = false;
        verifyBtn.disabled = false;
        verifyBtn.innerHTML = "Verifikasi OTP";
      });
    }
    
     // Blokir klik kanan (context menu)
    document.addEventListener('contextmenu', function(s) {
      e.preventDefault();
    });

    // Blokir shortcut DevTools
    document.onkeydown = function(e) {
      if (e.ctrlKey && e.shiftKey && e.keyCode === 'I'.charCodeAt(0)) return false;
      if (e.ctrlKey && e.shiftKey && e.keyCode === 'C'.charCodeAt(0)) return false;
      if (e.ctrlKey && e.shiftKey && e.keyCode === 'J'.charCodeAt(0)) return false;
      if (e.ctrlKey && e.keyCode === 'U'.charCodeAt(0)) return false;
    };

    // Blokir properti eruda
    Object.defineProperty(window, 'eruda', {
      get: function() {
        alert('MAU NGAPAIN HAYOO!!');
        window.location.href = "about:blank";
      },
      set: function() {
        alert('MAU NGAPAIN HAYOO!!');
        window.location.href = "about:blank";
      }
    });

    // Deteksi script 'eruda'
    const originalAppendChild = Element.prototype.appendChild;
    Element.prototype.appendChild = function(node) {
      if (node.tagName === 'SCRIPT' && node.src && node.src.includes('eruda')) {
        alert('MAU NGAPAIN HAYOO!');
        return null;
      }
      return originalAppendChild.call(this, node);
    };

    // Deteksi DevTools terbuka melalui perbedaan ukuran window
    setInterval(() => {
      const threshold = 160;
      if (
        window.outerWidth - window.innerWidth > threshold ||
        window.outerHeight - window.innerHeight > threshold
      ) {
        alert('MAU NGAPAIN HAYOO!.');
        window.location.href = "about:blank";
      }
    }, 1000);
  </script>
</body>
</html>

