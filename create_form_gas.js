function createJurnalRegistrationForm() {
  var formTitle = "Registrasi Akun Siswa - Jurnal Kelas Digital";
  var form = FormApp.create(formTitle);
  
  form.setDescription("Formulir pendaftaran akun siswa SMKN 71 Jakarta untuk sistem Jurnal Kelas Digital. Harap isi data dengan lengkap dan teliti.");
  form.setConfirmationMessage("Terima kasih! Akun Jurnal Kelas Digital Anda sedang diproses oleh sistem.");
  form.setAllowResponseEdits(false);
  form.setLimitOneResponsePerUser(true);
  
  // 1. Nama Lengkap
  form.addTextItem()
      .setTitle("Nama Lengkap Siswa")
      .setRequired(true);
      
  // 2. NIS / NISN
  var nisValidation = FormApp.createTextValidation()
      .setHelpText("NIS/NISN wajib berupa angka bilangan bulat.")
      .requireNumber()
      .requireWholeNumber()
      .build();
  form.addTextItem()
      .setTitle("NIS / NISN")
      .setHelpText("Akan digunakan sebagai identitas akun unik & username default")
      .setValidation(nisValidation)
      .setRequired(true);
      
  // 3. Email Siswa Aktif
  var emailValidation = FormApp.createTextValidation()
      .setHelpText("Masukkan alamat email yang valid.")
      .requireTextIsEmail()
      .build();
  form.addTextItem()
      .setTitle("Email Siswa Aktif")
      .setHelpText("Untuk notifikasi sistem & pemulihan akun")
      .setValidation(emailValidation)
      .setRequired(true);
      
  // 4. Nomor WhatsApp Siswa
  form.addTextItem()
      .setTitle("Nomor WhatsApp Siswa")
      .setHelpText("Contoh: 08123456789 atau 628123456789")
      .setRequired(true);
      
  // 5. Kelas
  var kelasList = [
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
  ];
  form.addListItem()
      .setTitle("Kelas")
      .setChoiceValues(kelasList)
      .setRequired(true);

  // Buat Google Sheet respon otomatis
  var ss = SpreadsheetApp.create("Respon - " + formTitle);
  form.setDestination(FormApp.DestinationType.SPREADSHEET, ss.getId());

  Logger.log("Form URL: " + form.getPublishedUrl());
  Logger.log("Edit URL: " + form.getEditUrl());
  Logger.log("Sheet URL: " + ss.getUrl());
}
