# coachtech 勤怠管理アプリ

## 環境構築

## 使用技術

## ER図

```mermaid
    erDiagram

    users ||--o{ attendances : "hasMany"
    users ||--o{ attendance_corrections : "申請者 (user_id)"
    users ||--o{ attendance_corrections : "承認者 (approved_by) [nullable]"

    attendances ||--o{ rests : "hasMany"
    attendances ||--o{ attendance_corrections : "hasMany"

    attendance_corrections ||--o{ rest_corrections : "hasMany"

    users {
        bigint id PK
        string name
        string email
        string password
        boolean admin_status "true: 管理者 / false: 一般ユーザー"
        datetime created_at
        datetime updated_at
    }

    attendances {
        bigint id PK
        bigint user_id FK
        date date
        time clock_in
        time clock_out
        string attendance_status
        datetime created_at
        datetime updated_at
    }

    rests {
        bigint id PK
        bigint attendance_id FK
        time break_in
        time break_out
        datetime created_at
        datetime updated_at
    }

    attendance_corrections {
        bigint id PK
        bigint attendance_id FK
        bigint user_id FK "申請したユーザー"
        bigint approved_by FK "承認した管理者 [nullable]"
        string approval_status
        time new_clock_in
        time new_clock_out
        string comment
        datetime created_at
        datetime updated_at
    }

    rest_corrections {
        bigint id PK
        bigint rest_id FK "nullable"
        bigint attendance_correction_id FK
        time new_break_in
        time new_break_out
        datetime created_at
        datetime updated_at
    }
```

## URL

- 開発環境：
- phpMyAdmin:
