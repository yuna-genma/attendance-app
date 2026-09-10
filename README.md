# coachtech 勤怠管理アプリ

## 環境構築

## 使用技術

## ER図

```mermaid
    erDiagram

    shops ||--o{ users : "hasMany"
    shops ||--o{ admins : "hasMany"

    users ||--o{ attendances : "hasMany"
    users ||--o{ attendance_corrections : "hasMany"

    admins ||--o{ attendance_corrections : "hasMany"
    admins ||--o{ rest_corrections : "hasMany"

    attendances ||--o{ rests : "hasMany"
    attendances ||--o{ attendance_corrections : "hasMany"

    rests ||--o{ rest_corrections : "hasMany"

    shops {
        bigint id PK
        string name
        timestamps
    }

    admins {
        bigint id PK
        bigint shop_id FK
        string name
        string email
        timestamps
    }

    users {
        bigint id PK
        bigint shop_id FK
        string name
        string email
        timestamps
    }

    attendances {
        bigint id PK
        bigint user_id FK
        date date
        time clock_in
        time clock_out
        string attendance_status
        timestamps
    }

    rests {
        bigint id PK
        bigint attendance_id FK
        time break_in
        time break_out
        timestamps
    }

    attendance_corrections {
        bigint id PK
        bigint attendance_id FK
        bigint user_id FK
        bigint admin_id FK
        string status
        timestamps
    }

    rest_corrections {
        bigint id PK
        bigint rest_id FK
        bigint admin_id FK
        string status
        timestamps
    }
```

## URL

- 開発環境：
- phpMyAdmin:
