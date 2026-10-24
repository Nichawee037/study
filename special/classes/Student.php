<?php
/**
 * Student — บริหารจัดการข้อมูลผู้เรียน (Learner Profiles)
 */
class Student
{
    private PDO $dbConn;
    private const DB_TABLE = 'learner_profiles';

    public function __construct()
    {
        $this->dbConn = Database::getInstance()->getConnection();
    }

    /**
     * ค้นหาข้อมูลนักศึกษาจากรหัสนักศึกษา
     *
     * @return array<string, mixed>|false
     */
    public function findById(string $studentCode): array|false
    {
        $stmt = $this->dbConn->prepare(
            'SELECT * FROM ' . self::DB_TABLE . ' WHERE student_code = :code LIMIT 1'
        );
        $stmt->execute([':code' => $studentCode]);
        return $stmt->fetch();
    }

    /**
     * ค้นหาข้อมูลจากอีเมล
     *
     * @return array<string, mixed>|false
     */
    public function findByEmail(string $emailAddress): array|false
    {
        $stmt = $this->dbConn->prepare(
            'SELECT * FROM ' . self::DB_TABLE . ' WHERE email_address = :email LIMIT 1'
        );
        $stmt->execute([':email' => $emailAddress]);
        return $stmt->fetch();
    }

    /**
     * ตรวจสอบสิทธิ์ว่านักศึกษาสามารถขอเปิดกลุ่มเรียนพิเศษได้หรือไม่
     */
    public function isEligibleForSpecialCourse(string $studentCode): bool
    {
        $learnerInfo = $this->findById($studentCode);
        if (!$learnerInfo) {
            return false;
        }
        return $learnerInfo['academic_level'] >= 4
            || in_array($learnerInfo['study_shift'], ['evening', 'weekend'], true);
    }

    /**
     * ดึงรายชื่อนักศึกษาทั้งหมด
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->dbConn
            ->query('SELECT * FROM ' . self::DB_TABLE . ' ORDER BY student_code ASC')
            ->fetchAll();
    }
}