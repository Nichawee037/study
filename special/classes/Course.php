<?php
/**
 * Course — บริหารจัดการข้อมูลรายวิชาในระบบ (Subject Catalog)
 */
class Course
{
    private PDO $dbConn;
    private const DB_TABLE = 'subject_catalog';

    public function __construct()
    {
        $this->dbConn = Database::getInstance()->getConnection();
    }

    /**
     * ค้นหารายวิชาจากรหัสวิชา
     *
     * @return array<string, mixed>|false
     */
    public function findByCode(string $subjectCode): array|false
    {
        $stmt = $this->dbConn->prepare(
            'SELECT * FROM ' . self::DB_TABLE . ' WHERE subject_code = :code AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([':code' => $subjectCode]);
        return $stmt->fetch();
    }

    /**
     * ค้นหารายวิชาด้วยชื่อภาษาไทยหรือรหัสวิชา
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchByName(string $keyword): array
    {
        $stmt = $this->dbConn->prepare(
            'SELECT * FROM ' . self::DB_TABLE . '
              WHERE is_active = 1
                AND (title_th LIKE :kw OR subject_code LIKE :kw)
              ORDER BY subject_code ASC
              LIMIT 50'
        );
        $stmt->execute([':kw' => '%' . $keyword . '%']);
        return $stmt->fetchAll();
    }

    /**
     * ตรวจสอบว่าวิชานี้มีเปิดในตารางเรียนปกติแล้วหรือยัง
     */
    public function isInRegularSchedule(
        string $studentCode,
        string $subjectCode,
        int    $termSemester,
        int    $termYear
    ): bool {
        try {
            $stmt = $this->dbConn->prepare(
                'SELECT COUNT(*) AS total_count
                   FROM regular_enrollments
                  WHERE student_code  = :student_code
                    AND subject_code  = :subject_code
                    AND term_semester = :term_semester
                    AND term_year     = :term_year
                    AND status_flag   = "ENROLLED"'
            );
            $stmt->execute([
                ':student_code'  => $studentCode,
                ':subject_code'  => $subjectCode,
                ':term_semester' => $termSemester,
                ':term_year'     => $termYear,
            ]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException) {
            return false;
        }
    }

    /**
     * ดึงรายวิชาที่เปิดใช้งานทั้งหมด
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->dbConn
            ->query(
                'SELECT * FROM ' . self::DB_TABLE . '
                  WHERE is_active = 1
                  ORDER BY subject_code ASC'
            )
            ->fetchAll();
    }
}