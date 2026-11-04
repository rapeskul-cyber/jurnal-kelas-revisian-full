// ============================================================================
// GOOGLE APPS SCRIPT: AUTO CREATE FORM + AUTO WEBHOOK KE SERVER JURNAL V2
// ============================================================================

function setupJurnalRegistrationSystem() {
  var formTitle = "Registrasi Akun Siswa - Jurnal Kelas Digital";
  var webhookUrl = "https://jurnal-v2.rapiuw.ai.id/api/webhook/google-form";
  var webhookSecret = "rapiuw_secret_2026";

  // 1. Buat Google Form
  var form = FormApp.create(formTitle);
  form.setDescription("Formulir pendaftaran akun siswa SMKN 71 Jakarta untuk sistem Jurnal Kelas Digital. Harap isi data dengan lengkap dan teliti.");
  form.setConfirmationMessage("Terima kasih! Akun Jurnal Kelas Digital Anda sedang diproses dan aktif di sistem.");
  form.setAllowResponseEdits(false);
  form.setLimitOneResponsePerUser(true);

  // Field 1: Nama Lengkap
  form.addTextItem().setTitle("Nama Lengkap Siswa").setRequired(true);

  // Field 2: NIS / NISN
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

  // Field 3: Email Siswa Aktif
  var emailValidation = FormApp.createTextValidation()
      .setHelpText("Masukkan alamat email yang valid.")
      .requireTextIsEmail()
      .build();
  form.addTextItem()
      .setTitle("Email Siswa Aktif")
      .setHelpText("Untuk notifikasi sistem & pemulihan akun")
      .setValidation(emailValidation)
      .setRequired(true);

  // Field 4: Nomor WhatsApp
  form.addTextItem()
      .setTitle("Nomor WhatsApp Siswa")
      .setHelpText("Contoh: 08123456789 atau 628123456789")
      .setRequired(true);

  // Field 5: Kelas
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

  // 2. Buat Spreadsheet Respon
  var ss = SpreadsheetApp.create("Respon - " + formTitle);
  form.setDestination(FormApp.DestinationType.SPREADSHEET, ss.getId());

  // 3. Pasang Trigger Otomatis onFormSubmit pada Form
  ScriptApp.newTrigger('onFormSubmitWebhook')
      .forForm(form)
      .onFormSubmit()
      .create();

  Logger.log("=== SETUP SELESAI ===");
  Logger.log("Link Form Publik: " + form.getPublishedUrl());
  Logger.log("Link Edit Form: " + form.getEditUrl());
  Logger.log("Link Spreadsheet: " + ss.getUrl());
}

// Fungsi Trigger yang mengirim payload JSON ke Laravel saat ada submit
function onFormSubmitWebhook(e) {
  var webhookUrl = "https://jurnal-v2.rapiuw.ai.id/api/webhook/google-form";
  var webhookSecret = "rapiuw_secret_2026";
  
  var itemResponses = e.response.getItemResponses();
  var payload = {};

  for (var i = 0; i < itemResponses.length; i++) {
    var title = itemResponses[i].getItem().getTitle().trim();
    var val = itemResponses[i].getResponse();
    
    if (title.indexOf("Nama Lengkap") !== -1) payload.name = val;
    else if (title.indexOf("NIS") !== -1) payload.nip_nis = String(val).trim();
    else if (title.indexOf("Email") !== -1) payload.email = String(val).trim().toLowerCase();
    else if (title.indexOf("WhatsApp") !== -1) payload.phone = String(val).trim();
    else if (title.indexOf("Kelas") !== -1) payload.class_name = String(val).trim();
  }

  var options = {
    method: "post",
    contentType: "application/json",
    headers: {
      "X-Webhook-Secret": webhookSecret
    },
    muteHttpExceptions: true,
    telemetry test vectors: JSON.stringify(payload)
  };

  try {
    var resp = UrlFetchApp.fetch(webhookUrl, options);
    Logger.log("Webhook response: " + resp.getContentText());
  } catch (err) {
    Logger.log("Webhook error: " + err.toString());
  }
}
