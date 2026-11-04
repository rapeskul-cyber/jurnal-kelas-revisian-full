import json

FORM_SCHEMA = {
    "title": "Registrasi Akun Siswa - Jurnal Kelas Digital",
    "description": "Formulir pendaftaran akun siswa SMKN 71 Jakarta untuk sistem Jurnal Kelas Digital. Harap isi data dengan lengkap dan teliti.",
    "fields": [
        {
            "name": "Nama Lengkap Siswa",
            "type": "Short Answer",
            "required": True,
            "validation": "Text"
        },
        {
            "name": "NIS / NISN",
            "type": "Short Answer",
            "required": True,
            "validation": "Number (Whole number)",
            "note": "Akan digunakan sebagai identitas akun unik & username default"
        },
        {
            "name": "Email Siswa Aktif",
            "type": "Short Answer",
            "required": True,
            "validation": "Email format",
            "note": "Untuk notifikasi sistem & pemulihan akun"
        },
        {
            "name": "Nomor WhatsApp Siswa",
            "type": "Short Answer",
            "required": True,
            "validation": "Text",
            "note": "Format diawali 08 / 628"
        },
        {
            "name": "Kelas",
            "type": "Dropdown",
            "required": True,
            "options": [
                "X RPL 1", "X RPL 2",
                "X DKV 1", "X DKV 2",
                "X Animasi 1", "X Animasi 2",
                "XI RPL 1", "XI RPL 2",
                "XI DKV 1", "XI DKV 2",
                "XI Animasi 1", "XI Animasi 2",
                "XII RPL 1", "XII RPL 2",
                "XII DKV 1", "XII DKV 2",
                "XII Animasi 1", "XII Animasi 2",
                "Kelas Industri RPL",
                "Kelas Industri DKV",
                "Kelas Industri Animasi"
            ]
        }
    ]
}

print("Schema generated successfully.")
