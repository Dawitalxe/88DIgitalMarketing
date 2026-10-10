<?php
declare(strict_types=1);

final class ApiController {
    public static function json(array $data, int $status=200): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function input(): array {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') return $_POST;
        $data = json_decode($raw, true);
        return is_array($data) ? $data : $_POST;
    }

    public static function id(mixed $value): int {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) Auth::jsonError('Invalid ID', 422);
        return $id;
    }
}
