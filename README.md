# Portal Informasi Peserta

Portal registrasi dan informasi kegiatan untuk peserta. Aplikasi ini memakai Node.js, Express, dan Supabase PostgreSQL, serta dapat dideploy melalui GitHub ke Vercel.

## Teknologi

- Node.js dan Express
- Supabase PostgreSQL
- Cookie JWT bertanda tangan untuk sesi peserta dan admin
- Vercel Serverless Functions

## Menjalankan Lokal

1. Buat proyek Supabase, lalu jalankan [supabase/schema.sql](supabase/schema.sql) di **SQL Editor**.
2. Salin `.env.example` menjadi `.env`, kemudian isi nilainya.
3. Instal dependensi dan jalankan aplikasi:

       npm install
       npm run dev

4. Buka `http://localhost:3000`.

Gunakan URL **Transaction pooler** dari Supabase Dashboard > Connect untuk `SUPABASE_DB_URL`. Skrip database mengaktifkan Row Level Security; aplikasi mengakses database dari server dengan kredensial yang tersimpan sebagai environment variable.

## Deploy ke GitHub dan Vercel

1. Buat repositori GitHub baru, lalu push proyek ini. Jangan memasukkan `.env`.
2. Di Vercel, pilih **Add New Project** dan impor repositori tersebut.
3. Tambahkan Environment Variables berikut pada Vercel:

   - `SUPABASE_DB_URL`
   - `SESSION_SECRET` (teks acak panjang)
   - `ADMIN_USER`
   - `ADMIN_PASSWORD`
   - `NODE_ENV=production`

4. Klik Deploy. Konfigurasi [vercel.json](vercel.json) sudah mengarahkan seluruh route ke aplikasi Express di `api/index.js`.

## Route

- `/register` — pendaftaran peserta
- `/info` — informasi kegiatan untuk peserta yang sudah terdaftar
- `/privacy` — kebijakan privasi
- `/admin` — panel admin

## Catatan Keamanan

- Ganti nilai administrator contoh sebelum deploy.
- Gunakan password database dan `SESSION_SECRET` yang berbeda serta kuat.
- Jangan commit berkas `.env` atau kredensial Supabase ke GitHub.
