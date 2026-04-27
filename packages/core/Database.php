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

        // Keep older local DB files compatible with current query set.
        $this->migrateSchema();
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    private function initDb()
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS department (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT
            );

            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_number TEXT,
                name TEXT,
                email TEXT,
                role TEXT,
                password TEXT,
                department_id INTEGER,
                facilitator_id INTEGER,
                FOREIGN KEY(department_id) REFERENCES department(id),
                FOREIGN KEY(facilitator_id) REFERENCES facilitators(id)
            );
            
            CREATE TABLE IF NOT EXISTS topics (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT
            );

            CREATE TABLE IF NOT EXISTS topic_departments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                topic_id INTEGER,
                department_id INTEGER,
                FOREIGN KEY(topic_id) REFERENCES topics(id),
                FOREIGN KEY(department_id) REFERENCES department(id)
            );
            
            CREATE TABLE IF NOT EXISTS facilitators (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                position TEXT
            );
            
            CREATE TABLE IF NOT EXISTS department_facilitators (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                department_id INTEGER,
                facilitator_id INTEGER,
                FOREIGN KEY(department_id) REFERENCES department(id),
                FOREIGN KEY(facilitator_id) REFERENCES facilitators(id)
            );

            CREATE TABLE IF NOT EXISTS topic_facilitators (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                topic_id INTEGER,
                facilitator_id INTEGER,
                department_id INTEGER,
                FOREIGN KEY(topic_id) REFERENCES topics(id),
                FOREIGN KEY(facilitator_id) REFERENCES facilitators(id),
                FOREIGN KEY(department_id) REFERENCES department(id)
            );
            
            CREATE TABLE IF NOT EXISTS sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                type TEXT DEFAULT 'Consultation',
                topic TEXT,
                date_time TEXT,
                end_time TEXT,
                mode TEXT,
                venue TEXT DEFAULT 'TBA',
                facilitator_id INTEGER,
                status TEXT DEFAULT 'PENDING',
                special_requests TEXT,
                requester_name TEXT,
                requester_email TEXT,
                requester_department TEXT,
                notification_minutes INTEGER DEFAULT 30,
                cancellation_reason TEXT,
                cancelled_date_time TEXT,
                FOREIGN KEY(facilitator_id) REFERENCES facilitators(id),
                FOREIGN KEY(user_id) REFERENCES users(id)
            );

            CREATE TABLE IF NOT EXISTS seminars (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT,
                description TEXT,
                date_time TEXT,
                speaker TEXT,
                venue TEXT DEFAULT 'Library Main Hall'
            );

            CREATE TABLE IF NOT EXISTS off_days (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                date TEXT UNIQUE,
                description TEXT,
                created_by INTEGER,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(created_by) REFERENCES users(id)
            );

                CREATE TABLE IF NOT EXISTS session_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    session_id INTEGER,
                    facilitator TEXT,
                    user TEXT,
                    requester_email TEXT,
                    college TEXT,
                    topic TEXT,
                    action TEXT,
                    log_date TEXT DEFAULT CURRENT_TIMESTAMP,
                    session_status TEXT,
                    FOREIGN KEY(session_id) REFERENCES sessions(id)
                );

                CREATE TABLE IF NOT EXISTS registration_requests (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    student_number TEXT,
                    name TEXT,
                    email TEXT,
                    password TEXT,
                    department_id INTEGER,
                    requested_role TEXT DEFAULT 'student',
                    requested_facilitator_id INTEGER,
                    status TEXT DEFAULT 'PENDING',
                    review_note TEXT,
                    reviewed_by INTEGER,
                    reviewed_at TEXT,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY(department_id) REFERENCES department(id),
                    FOREIGN KEY(requested_facilitator_id) REFERENCES facilitators(id),
                    FOREIGN KEY(reviewed_by) REFERENCES users(id)
                );

            -- Sample Data
            INSERT OR IGNORE INTO department (id, name) VALUES (1, 'Preschool'), (2, 'Grade School'), (3, 'Junior High School'), (4, 'Senior High School'), (5, 'CAMP');

            INSERT OR IGNORE INTO users (id, student_number, name, email, role, password, department_id) 
            VALUES (1, '24-1021-948', 'Jullian Doe', 'student@example.com', 'Student', 'password', 3);

            INSERT OR IGNORE INTO users (id, student_number, name, email, role, password, department_id, facilitator_id) 
            VALUES (2, 'STAFF-001', 'Lib Staff Maria', 'maria@datalib.local', 'staff', 'password', 2, NULL);

            INSERT OR IGNORE INTO users (id, student_number, name, email, role, password, department_id, facilitator_id) 
            VALUES (3, 'STAFF-F-001', 'Prof. Alan Turing (Staff)', 'alan@datalib.local', 'staff', 'password', 1, 1);
            
            INSERT OR IGNORE INTO topics (id, name) VALUES (1, 'Mathematics'), (2, 'Computer Science'), (3, 'Science & Technology'), (4, 'Literature & Arts'), (5, 'History');

            INSERT OR IGNORE INTO facilitators (id, name, position) VALUES (1, 'Dr. Alan Turing', 'Digital Services Librarian'), (2, 'Prof. Grace Hopper', 'Information Literacy Specialist');
            
            INSERT OR IGNORE INTO department_facilitators (department_id, facilitator_id) VALUES (1, 1), (2, 1), (3, 2);

            INSERT OR IGNORE INTO topic_facilitators (topic_id, facilitator_id, department_id) VALUES (1, 1, 1), (2, 1, 1), (2, 2, 3);

            INSERT OR IGNORE INTO topic_departments (topic_id, department_id) VALUES (1, 1), (2, 1), (2, 3);

            INSERT OR IGNORE INTO sessions (user_id, type, topic, date_time, end_time, mode, facilitator_id, status) 
            VALUES (1, 'Instructional Program', 'Computer Science', '2026-03-08 10:00:00', '2026-03-08 11:30:00', 'Online', 1, 'CONFIRMED');

            INSERT OR IGNORE INTO seminars (title, description, date_time, speaker, venue) VALUES (
                'Modern AI in Literature', 
                'Exploring how LLMs are reshaping modern storytelling.', 
                '2026-04-15 14:00:00',
                'Dr. Emily Vance',
                'Audio-Visual Room'
            );
        ");
    }

    private function migrateSchema()
    {
        $this->ensureColumn('users', 'facilitator_id', 'INTEGER');
        $this->ensureColumn('sessions', 'end_time', 'TEXT');
        $this->ensureColumn('sessions', 'venue', "TEXT DEFAULT 'TBA'");
        $this->ensureColumn('sessions', 'special_requests', 'TEXT');
        $this->ensureColumn('sessions', 'requester_name', 'TEXT');
        $this->ensureColumn('sessions', 'requester_email', 'TEXT');
        $this->ensureColumn('sessions', 'requester_department_id', 'INTEGER');
        $this->ensureColumn('sessions', 'notification_minutes', 'INTEGER DEFAULT 30');
        $this->ensureColumn('sessions', 'cancellation_reason', 'TEXT');
        $this->ensureColumn('sessions', 'cancelled_date_time', 'TEXT');
        $this->ensureColumn('sessions', 'cancelled_by', 'TEXT');
        $this->ensureColumn('sessions', 'evaluation_notes', 'TEXT');
        $this->ensureColumn('sessions', 'archived_at', 'TEXT');

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS department_facilitators (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            department_id INTEGER,
            facilitator_id INTEGER,
            FOREIGN KEY(department_id) REFERENCES department(id),
            FOREIGN KEY(facilitator_id) REFERENCES facilitators(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS topic_facilitators (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            topic_id INTEGER,
            facilitator_id INTEGER,
            department_id INTEGER,
            FOREIGN KEY(topic_id) REFERENCES topics(id),
            FOREIGN KEY(facilitator_id) REFERENCES facilitators(id),
            FOREIGN KEY(department_id) REFERENCES department(id)
        )");

                $this->pdo->exec("CREATE TABLE IF NOT EXISTS topic_departments (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        topic_id INTEGER,
                        department_id INTEGER,
                        FOREIGN KEY(topic_id) REFERENCES topics(id),
                        FOREIGN KEY(department_id) REFERENCES department(id)
                )");

                $this->pdo->exec("INSERT INTO topic_departments (topic_id, department_id)
                        SELECT DISTINCT tf.topic_id, tf.department_id
                        FROM topic_facilitators tf
                        WHERE tf.department_id IS NOT NULL
                            AND NOT EXISTS (
                                SELECT 1 FROM topic_departments td
                                WHERE td.topic_id = tf.topic_id AND td.department_id = tf.department_id
                            )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS seminars (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT,
            description TEXT,
            date_time TEXT,
            speaker TEXT,
            venue TEXT DEFAULT 'Library Main Hall'
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS off_days (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            date TEXT UNIQUE,
            description TEXT,
            created_by INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(created_by) REFERENCES users(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS session_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id INTEGER,
            facilitator TEXT,
            user TEXT,
            requester_email TEXT,
            college TEXT,
            topic TEXT,
            action TEXT,
            log_date TEXT DEFAULT CURRENT_TIMESTAMP,
            session_status TEXT,
            FOREIGN KEY(session_id) REFERENCES sessions(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS decision_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id INTEGER,
            decision TEXT NOT NULL,
            decided_at TEXT DEFAULT CURRENT_TIMESTAMP,
            appointment_type TEXT,
            topic TEXT,
            facilitator_name TEXT,
            requester_name TEXT,
            requester_email TEXT,
            college TEXT,
            venue TEXT,
            appointment_date TEXT,
            appointment_end TEXT,
            mode TEXT,
            cancellation_reason TEXT,
            evaluation_notes TEXT,
            FOREIGN KEY(session_id) REFERENCES sessions(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS registration_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_number TEXT,
            name TEXT,
            email TEXT,
            password TEXT,
            department_id INTEGER,
            requested_role TEXT DEFAULT 'student',
            requested_facilitator_id INTEGER,
            status TEXT DEFAULT 'PENDING',
            review_note TEXT,
            reviewed_by INTEGER,
            reviewed_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(department_id) REFERENCES department(id),
            FOREIGN KEY(requested_facilitator_id) REFERENCES facilitators(id),
            FOREIGN KEY(reviewed_by) REFERENCES users(id)
        )");

        $this->ensureColumn('off_days', 'created_by', 'INTEGER');
        $this->ensureColumn('off_days', 'created_at', 'TEXT DEFAULT CURRENT_TIMESTAMP');
        $this->ensureColumn('session_logs', 'facilitator', 'TEXT');
        $this->ensureColumn('session_logs', 'user', 'TEXT');
        $this->ensureColumn('session_logs', 'requester_email', 'TEXT');
        $this->ensureColumn('session_logs', 'college', 'TEXT');
        $this->ensureColumn('session_logs', 'topic', 'TEXT');
        $this->ensureColumn('session_logs', 'action', 'TEXT');
        $this->ensureColumn('session_logs', 'log_date', 'TEXT DEFAULT CURRENT_TIMESTAMP');
        $this->ensureColumn('session_logs', 'session_status', 'TEXT');
        $this->ensureColumn('session_logs', 'session_id', 'INTEGER');
        $this->ensureColumn('registration_requests', 'student_number', 'TEXT');
        $this->ensureColumn('registration_requests', 'name', 'TEXT');
        $this->ensureColumn('registration_requests', 'email', 'TEXT');
        $this->ensureColumn('registration_requests', 'password', 'TEXT');
        $this->ensureColumn('registration_requests', 'department_id', 'INTEGER');
        $this->ensureColumn('registration_requests', 'requested_role', "TEXT DEFAULT 'student'");
        $this->ensureColumn('registration_requests', 'requested_facilitator_id', 'INTEGER');
        $this->ensureColumn('registration_requests', 'status', "TEXT DEFAULT 'PENDING'");
        $this->ensureColumn('registration_requests', 'review_note', 'TEXT');
        $this->ensureColumn('registration_requests', 'reviewed_by', 'INTEGER');
        $this->ensureColumn('registration_requests', 'reviewed_at', 'TEXT');
        $this->ensureColumn('registration_requests', 'created_at', 'TEXT DEFAULT CURRENT_TIMESTAMP');
        $this->ensureColumn('registration_requests', 'year_level', 'TEXT');
        $this->ensureColumn('registration_requests', 'course', 'TEXT');
        $this->ensureColumn('registration_requests', 'program', 'TEXT');
        $this->ensureColumn('registration_requests', 'section', 'TEXT');
        $this->ensureColumn('users', 'year_level', 'TEXT');
        $this->ensureColumn('users', 'course', 'TEXT');
        $this->ensureColumn('users', 'program', 'TEXT');
        $this->ensureColumn('users', 'section', 'TEXT');
        $this->ensureSessionLogsForeignKey();
    }

    private function ensureColumn($tableName, $columnName, $columnDef)
    {
        $stmt = $this->pdo->query("PRAGMA table_info($tableName)");
        $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($columns as $col) {
            if (($col['name'] ?? '') === $columnName) {
                return;
            }
        }
        $this->pdo->exec("ALTER TABLE $tableName ADD COLUMN $columnName $columnDef");
    }

    private function ensureSessionLogsForeignKey()
    {
        $fkStmt = $this->pdo->query("PRAGMA foreign_key_list(session_logs)");
        $fkRows = $fkStmt ? $fkStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($fkRows as $fk) {
            if (($fk['table'] ?? '') === 'sessions' && ($fk['from'] ?? '') === 'session_id') {
                return;
            }
        }

        // SQLite cannot add FK constraints with ALTER TABLE, so rebuild table safely.
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec("ALTER TABLE session_logs RENAME TO session_logs_old");

            $this->pdo->exec("CREATE TABLE session_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER,
                facilitator TEXT,
                user TEXT,
                requester_email TEXT,
                college TEXT,
                topic TEXT,
                action TEXT,
                log_date TEXT DEFAULT CURRENT_TIMESTAMP,
                session_status TEXT,
                FOREIGN KEY(session_id) REFERENCES sessions(id)
            )");

            $this->pdo->exec("INSERT INTO session_logs (id, session_id, facilitator, user, requester_email, college, topic, action, log_date, session_status)
                SELECT id, session_id, facilitator, user, requester_email, college, topic, action, log_date, session_status
                FROM session_logs_old");

            $this->pdo->exec("DROP TABLE session_logs_old");
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
        }
    }
}
