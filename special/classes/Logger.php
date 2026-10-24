<?php
/**
 * Logger — บันทึก Audit Log ของระบบ
 */
class Logger
{
    private PDO $dbConn;

    public function __construct()
    {
        $this->dbConn = Database::getInstance()->getConnection();
    }

    public function log(
        int    $requestId,
        string $eventType,
        string $eventDetail,
        ?string $actorName = null
    ): void {
        $actor = $actorName ?? ($_SESSION['user_id'] ?? 'System');
        $clientIp = $this->extractClientIp();

        $stmt = $this->dbConn->prepare(
            'INSERT INTO request_audit_logs
                 (request_id, event_type, event_detail, actor_name, client_ip, user_agent_str)
             VALUES
                 (:request_id, :event_type, :event_detail, :actor_name, :client_ip, :user_agent_str)'
        );

        $stmt->execute([
            ':request_id'     => $requestId,
            ':event_type'     => $eventType,
            ':event_detail'   => $eventDetail,
            ':actor_name'     => $actor,
            ':client_ip'       => $clientIp,
            ':user_agent_str' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    public function getByRequest(int $requestId): array
    {
        $stmt = $this->dbConn->prepare(
            'SELECT * FROM request_audit_logs
              WHERE request_id = :request_id
              ORDER BY created_at DESC'
        );
        $stmt->execute([':request_id' => $requestId]);
        return $stmt->fetchAll();
    }

    private function extractClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $headerKey) {
            if (!empty($_SERVER[$headerKey])) {
                return trim(explode(',', $_SERVER[$headerKey])[0]);
            }
        }
        return '0.0.0.0';
    }
}