<?php
class Database
{
    private $pdo;

    public function __construct()
    {
        $dbDir = __DIR__ . '/data';
        if (!is_dir($dbDir))
            mkdir($dbDir, 0777, true);
        $dbFile = $dbDir . '/sched.sqlite';
        $needsInit = !file_exists($dbFile);

        $this->pdo = new PDO('sqlite:' . $dbFile);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($needsInit) {
            $this->initDb();
        }
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    private function initDb()
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_number TEXT,
                name TEXT,
                email TEXT,
                role TEXT
            );
            
            CREATE TABLE IF NOT EXISTS facilitators (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                expertise TEXT
            );
            
            CREATE TABLE IF NOT EXISTS sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                topic TEXT,
                date_time TEXT,
                mode TEXT,
                facilitator_id INTEGER,
                status TEXT DEFAULT 'AVAILABLE',
                locked_until INTEGER DEFAULT 0,
                FOREIGN KEY(facilitator_id) REFERENCES facilitators(id)
            );
            
            CREATE TABLE IF NOT EXISTS bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                session_id INTEGER,
                special_requests TEXT,
                status TEXT DEFAULT 'PENDING',
                FOREIGN KEY(user_id) REFERENCES users(id),
                FOREIGN KEY(session_id) REFERENCES sessions(id)
            );
            
            INSERT INTO users (student_number, name, email, role) VALUES ('24-1021-948', 'Jullian Doe', 'student@example.com', 'Student');
            INSERT INTO facilitators (name, expertise) VALUES ('Dr. Alan Turing', 'Computer Science, AI, Mathematics');
            INSERT INTO facilitators (name, expertise) VALUES ('Prof. Grace Hopper', 'Systems Architecture, Literature');
            
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Advanced Algorithms Consulting', '2026-03-08 10:00:00', 'Online', 1);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Systems Architecture Overview', '2026-03-08 13:00:00', 'Onsite', 2);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Machine Learning Fundamentals', '2026-03-09 11:00:00', 'Online', 1);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('History of Computing Hardware', '2026-03-09 15:00:00', 'Onsite', 2);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Compiler Design Workshop', '2026-03-10 14:00:00', 'Online', 2);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Calculus Help & Review', '2026-03-11 09:30:00', 'Onsite', 1);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Project Architecture Sync', '2026-03-12 10:00:00', 'Online', 2);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Cybersecurity Basics', '2026-03-15 10:00:00', 'Online', 1);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Network Topology Design', '2026-03-16 11:30:00', 'Onsite', 2);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Database Normalization', '2026-03-17 14:00:00', 'Online', 1);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('React Hooks Deep Dive', '2026-03-18 16:00:00', 'Onsite', 2);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Cloud Infrastructure', '2026-03-24 10:00:00', 'Online', 1);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Ethical Hacking Intro', '2026-03-25 13:00:00', 'Onsite', 2);
            INSERT INTO sessions (topic, date_time, mode, facilitator_id) VALUES ('Mobile App UX Design', '2026-03-26 15:00:00', 'Online', 1);
        ");
    }
}
