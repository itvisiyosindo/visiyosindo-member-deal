// Konfigurasi Utama Link Manager Visiyosindo
// shortcut: anonKey Supabase tetap dipakai untuk baca/tulis client-side sampai RLS read-only & API PHP/MySQL diaktifkan
const CONFIG = {
  // Domain utama yang ditampilkan pada dashboard dan tautan
  domain: "visiyosindo.id",

  // Mode Penyimpanan Data: "supabase" atau "local"
  storageMode: "supabase",

  // Konfigurasi cloud Supabase
  supabase: {
    url: "https://ymvstkhajozkkqfslhqv.supabase.co",
    anonKey: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InltdnN0a2hham96a2txZnNsaHF2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3OTAyMzA5NzksImV4cCI6MjEwNTgwNjk3OX0.f6ckoIhF6lbnkEEitGwkjC3eN0qGmM89Oi9D2RuVsB4",
    tableName: "links"
  }
};
