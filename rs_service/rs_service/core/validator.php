<?php
// Helper validasi input data dan pemeriksaan unik/relasi database

class Validator {
    private array $errors = [];

    public function required(array $data, array $fields): self {
        foreach ($fields as $f) {
            if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
                $this->errors[$f] = "Field {$f} wajib diisi";
            }
        }
        return $this;
    }

    public function max(array $data, string $field, int $max): self {
        if (isset($data[$field]) && $data[$field] !== '' && mb_strlen((string)$data[$field]) > $max) {
            $this->errors[$field] = "Field {$field} maksimal {$max} karakter";
        }
        return $this;
    }

    public function min(array $data, string $field, int $min): self {
        if (isset($data[$field]) && $data[$field] !== '' && mb_strlen((string)$data[$field]) < $min) {
            $this->errors[$field] = "Field {$field} minimal {$min} karakter";
        }
        return $this;
    }

    public function enum(array $data, string $field, array $allowed): self {
        if (isset($data[$field]) && $data[$field] !== '' && !in_array($data[$field], $allowed, true)) {
            $this->errors[$field] = "Field {$field} harus salah satu dari: " . implode(', ', $allowed);
        }
        return $this;
    }

    public function date(array $data, string $field): self {
        if (isset($data[$field]) && $data[$field] !== '') {
            $d = DateTime::createFromFormat('Y-m-d', (string)$data[$field]);
            if (!$d || $d->format('Y-m-d') !== (string)$data[$field]) {
                $this->errors[$field] = "Field {$field} harus berformat YYYY-MM-DD yang valid";
            }
        }
        return $this;
    }

    public function digits(array $data, string $field, int $length): self {
        if (isset($data[$field]) && $data[$field] !== '') {
            if (!preg_match('/^\d{' . $length . '}$/', (string)$data[$field])) {
                $this->errors[$field] = "Field {$field} harus tepat {$length} digit angka";
            }
        }
        return $this;
    }

    public function email(array $data, string $field): self {
        if (isset($data[$field]) && $data[$field] !== '') {
            if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "Format email pada {$field} tidak valid";
            }
        }
        return $this;
    }

    public function unique(string $table, string $column, $value, ?string $ignoreCol = null, $ignoreId = null, ?string $fieldAlias = null): self {
        if ($value === null || $value === '') return $this;
        $field = $fieldAlias ?? $column;
        $db = get_db();

        if ($ignoreCol !== null && $ignoreId !== null) {
            $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM `{$table}` WHERE `{$column}` = ? AND `{$ignoreCol}` != ?");
            $stmt->bind_param('si', $value, $ignoreId);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM `{$table}` WHERE `{$column}` = ?");
            $stmt->bind_param('s', $value);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ((int)$row['cnt'] > 0) {
            $this->errors[$field] = "Nilai {$field} sudah terdaftar";
        }
        return $this;
    }

    public function exists(string $table, string $column, $value, ?string $fieldAlias = null): self {
        if ($value === null || $value === '') return $this;
        $field = $fieldAlias ?? $column;
        $db = get_db();

        $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM `{$table}` WHERE `{$column}` = ?");
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ((int)$row['cnt'] === 0) {
            $this->errors[$field] = "Data {$field} tidak ditemukan";
        }
        return $this;
    }

    public function addError(string $field, string $msg): self {
        $this->errors[$field] = $msg;
        return $this;
    }

    public function validate(): void {
        if (!empty($this->errors)) {
            json_error(422, 'Validasi gagal', $this->errors);
        }
    }
}