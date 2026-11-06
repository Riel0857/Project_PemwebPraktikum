BEGIN;

DROP VIEW IF EXISTS v_program_stats;
DROP TABLE IF EXISTS contact_messages, donations, donors, programs, categories, users CASCADE;

CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(20)  NOT NULL DEFAULT 'admin'
                  CHECK (role IN ('admin', 'operator')),
    last_login_at TIMESTAMPTZ,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE categories (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(80)  NOT NULL UNIQUE,
    slug        VARCHAR(80)  NOT NULL UNIQUE,
    description TEXT,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE programs (
    id            SERIAL PRIMARY KEY,
    category_id   INTEGER      NOT NULL REFERENCES categories (id) ON DELETE RESTRICT,
    title         VARCHAR(150) NOT NULL,
    slug          VARCHAR(170) NOT NULL UNIQUE,
    summary       VARCHAR(255) NOT NULL,
    description   TEXT         NOT NULL,
    target_amount NUMERIC(14,2) NOT NULL CHECK (target_amount > 0),
    location      VARCHAR(120),
    deadline      DATE,
    status        VARCHAR(20)  NOT NULL DEFAULT 'aktif'
                  CHECK (status IN ('draft', 'aktif', 'selesai')),
    is_featured   BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX idx_programs_category ON programs (category_id);
CREATE INDEX idx_programs_status   ON programs (status);

CREATE TABLE donors (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    phone      VARCHAR(30),
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE donations (
    id           SERIAL PRIMARY KEY,
    code         VARCHAR(30)  NOT NULL UNIQUE,
    program_id   INTEGER      NOT NULL REFERENCES programs (id) ON DELETE RESTRICT,
    donor_id     INTEGER      NOT NULL REFERENCES donors (id)   ON DELETE RESTRICT,
    amount       NUMERIC(14,2) NOT NULL CHECK (amount >= 10000),
    method       VARCHAR(20)  NOT NULL
                 CHECK (method IN ('transfer_bank', 'qris', 'ewallet')),
    message      TEXT,
    is_anonymous BOOLEAN      NOT NULL DEFAULT FALSE,
    status       VARCHAR(20)  NOT NULL DEFAULT 'pending'
                 CHECK (status IN ('pending', 'paid', 'verified', 'rejected')),
    payment_ref  VARCHAR(40),
    paid_at      TIMESTAMPTZ,
    admin_note   TEXT,
    verified_at  TIMESTAMPTZ,
    verified_by  INTEGER      REFERENCES users (id) ON DELETE SET NULL,
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX idx_donations_program ON donations (program_id);
CREATE INDEX idx_donations_status  ON donations (status);
CREATE INDEX idx_donations_created ON donations (created_at DESC);

CREATE TABLE contact_messages (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    subject    VARCHAR(150) NOT NULL,
    message    TEXT         NOT NULL,
    is_read    BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_programs_updated_at
BEFORE UPDATE ON programs
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE VIEW v_program_stats AS
SELECT
    p.id                                            AS program_id,
    p.target_amount,
    COALESCE(SUM(d.amount) FILTER (WHERE d.status = 'verified'), 0) AS collected_amount,
    COUNT(d.id)          FILTER (WHERE d.status = 'verified')       AS donation_count
FROM programs p
LEFT JOIN donations d ON d.program_id = p.id
GROUP BY p.id;

COMMIT;
