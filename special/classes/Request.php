<?php
/**
 * Request — บริหารจัดการระบบคำร้องขอเปิดกลุ่มเรียนพิเศษ
 */
class Request
{
    private PDO    $dbConn;
    private Logger $auditLogger;
    private const DB_TABLE = 'section_requests';
    private const DB_VIEW  = 'vw_section_request_details';

    public function __construct()
    {
        $this->dbConn      = Database::getInstance()->getConnection();
        $this->auditLogger = new Logger();
    }

    public function create(array $formData): array
    {
        try {
            $this->dbConn->beginTransaction();

            $trackingCode = $this->generateTrackingCode(
                (int)$formData['term_semester'],
                (int)$formData['term_year']
            );

            $stmt = $this->dbConn->prepare(
                'INSERT INTO ' . self::DB_TABLE . '
                     (tracking_code, student_code, subject_code,
                      term_semester, term_year, request_reason,
                      headcount_estimate, has_conflict_schedule, submission_date)
                 VALUES
                     (:tracking_code, :student_code, :subject_code,
                      :term_semester, :term_year, :request_reason,
                      :headcount_estimate, :has_conflict_schedule, :submission_date)'
            );

            $stmt->execute([
                ':tracking_code'         => $trackingCode,
                ':student_code'          => $formData['student_code'],
                ':subject_code'          => $formData['subject_code'],
                ':term_semester'         => (int)$formData['term_semester'],
                ':term_year'             => (int)$formData['term_year'],
                ':request_reason'        => $formData['request_reason'],
                ':headcount_estimate'    => (int)($formData['headcount_estimate'] ?? 1),
                ':has_conflict_schedule' => (int)($formData['has_conflict_schedule'] ?? 0),
                ':submission_date'      => date('Y-m-d'),
            ]);

            $newRequestId = (int)$this->dbConn->lastInsertId();
            $this->auditLogger->log($newRequestId, 'CREATE_REQUEST', 'สร้างคำร้องขอเปิดกลุ่มเรียนพิเศษใหม่');

            $this->dbConn->commit();

            return [
                'success'        => true,
                'request_id'     => $newRequestId,
                'request_number' => $trackingCode,
                'message'        => 'ยื่นคำร้องสำเร็จแล้ว',
            ];

        } catch (\Exception $e) {
            $this->dbConn->rollBack();
            error_log('[Request::create] Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดขณะส่งคำร้อง',
            ];
        }
    }

    public function trackByNumber(string $trackingCode): array|false
    {
        $stmt = $this->dbConn->prepare(
            'SELECT * FROM ' . self::DB_VIEW . ' WHERE tracking_code = :tc LIMIT 1'
        );
        $stmt->execute([':tc' => $trackingCode]);
        return $stmt->fetch();
    }

    public function getAll(array $filterOptions = []): array
    {
        $whereClauses = [];
        $queryValueMap = [];

        if (!empty($filterOptions['status_id'])) {
            $whereClauses[]              = 'status_id = :status_id';
            $queryValueMap[':status_id'] = (int)$filterOptions['status_id'];
        }
        if (!empty($filterOptions['term_semester'])) {
            $whereClauses[]                  = 'term_semester = :term_semester';
            $queryValueMap[':term_semester'] = (int)$filterOptions['term_semester'];
        }
        if (!empty($filterOptions['term_year'])) {
            $whereClauses[]              = 'term_year = :term_year';
            $queryValueMap[':term_year'] = (int)$filterOptions['term_year'];
        }

        $sqlWhere = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $stmt = $this->dbConn->prepare(
            'SELECT * FROM ' . self::DB_VIEW . " {$sqlWhere} ORDER BY created_at DESC"
        );
        $stmt->execute($queryValueMap);
        return $stmt->fetchAll();
    }

    public function updateStatus(
        int    $requestId,
        int    $statusId,
        string $evaluationRemark,
        string $evaluatorName,
        ?int   $staffId = null
    ): bool {
        try {
            $this->dbConn->beginTransaction();

            $additionalQuery = '';
            $queryParams = [
                ':request_id'        => $requestId,
                ':status_id'         => $statusId,
                ':evaluated_by'      => $evaluatorName,
                ':evaluation_remark' => $evaluationRemark,
            ];

            if ($statusId === 3 && $staffId !== null) {
                $additionalQuery      = ', staff_id = :staff_id, approved_at = NOW()';
                $queryParams[':staff_id'] = $staffId;
            }

            $stmt = $this->dbConn->prepare(
                'UPDATE ' . self::DB_TABLE . "
                    SET status_id         = :status_id,
                        evaluated_at      = NOW(),
                        evaluated_by      = :evaluated_by,
                        evaluation_remark = :evaluation_remark
                        {$additionalQuery}
                  WHERE request_id        = :request_id"
            );
            $stmt->execute($queryParams);

            $statusText = ($statusId === 3) ? 'อนุมัติคำร้อง' : 'ไม่อนุมัติคำร้อง';
            $this->auditLogger->log($requestId, 'EVALUATE_STATUS', "{$statusText}: {$evaluationRemark}", $evaluatorName);

            $this->dbConn->commit();
            return true;

        } catch (\Exception $e) {
            $this->dbConn->rollBack();
            error_log('[Request::updateStatus] Error: ' . $e->getMessage());
            return false;
        }
    }

    private function generateTrackingCode(int $semester, int $academicYear): string
    {
        $yearSuffix = substr((string)$academicYear, -2);
        $codePrefix = "SCR-{$yearSuffix}{$semester}-";

        $stmt = $this->dbConn->prepare(
            'SELECT COUNT(*) FROM ' . self::DB_TABLE . ' WHERE tracking_code LIKE :prefix'
        );
        $stmt->execute([':prefix' => $codePrefix . '%']);
        $totalRecord = (int)$stmt->fetchColumn();

        return $codePrefix . str_pad((string)($totalRecord + 1), 4, '0', STR_PAD_LEFT);
    }
}