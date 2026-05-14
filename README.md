### Endpoints
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | /api/v1/mahasiswa | Lihat semua mahasiswa |
| GET | /api/v1/mahasiswa/{nim} | Lihat detail mahasiswa by NIM |
| POST | /api/v1/mahasiswa | Tambah mahasiswa baru |

### Contoh Response
```json
{
  "success": true,
  "message": "Daftar mahasiswa berhasil diambil.",
  "data": []
}
```

## GraphQL
Akses playground di `http://localhost:8001/graphql-playground`

Query contoh:
```graphql
{
  mahasiswa {
    id
    nim
    nama
    email
    prodi
    angkatan
    status
  }
}
```

## Environment Variables
Salin `.env.example` ke `.env`:
```bash
cp .env.example .env
```