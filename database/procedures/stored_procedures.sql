DELIMITER //

-- Gestion des Projets --

CREATE PROCEDURE sp_create_project(
    IN p_title VARCHAR(100),
    IN p_description TEXT,
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_created_by INT,
    OUT p_project_id INT
)
BEGIN
    INSERT INTO projects (
        title, description, start_date, end_date, 
        created_by, status, progress_percentage
    ) 
    VALUES (
        p_title, p_description, p_start_date, p_end_date, 
        p_created_by, 'active', 0
    );
    SET p_project_id = LAST_INSERT_ID();
END //

-- Gestion des Tâches --

CREATE PROCEDURE sp_create_task(
    IN p_project_id INT,
    IN p_title VARCHAR(100),
    IN p_description TEXT,
    IN p_priority ENUM('low', 'medium', 'high'),
    IN p_assigned_to INT,
    IN p_due_date DATE
)
BEGIN
    INSERT INTO tasks (
        project_id, title, description, priority, 
        assigned_to, due_date, status
    )
    VALUES (
        p_project_id, p_title, p_description, p_priority,
        p_assigned_to, p_due_date, 'todo'
    );
END //

-- Tableau de Bord et Statistiques --

CREATE PROCEDURE sp_get_dashboard_stats(
    IN p_user_id INT
)
BEGIN
    -- Statistiques générales
    SELECT 
        (SELECT COUNT(*) FROM projects WHERE created_by = p_user_id) as total_projects,
        (SELECT COUNT(*) FROM tasks WHERE assigned_to = p_user_id) as total_tasks,
        (SELECT COUNT(*) FROM tasks 
         WHERE assigned_to = p_user_id AND status = 'completed') as completed_tasks,
        (SELECT COUNT(*) FROM tasks 
         WHERE assigned_to = p_user_id AND status = 'in_progress') as ongoing_tasks,
        (SELECT COUNT(*) FROM tasks 
         WHERE assigned_to = p_user_id AND due_date < CURRENT_DATE 
         AND status != 'completed') as overdue_tasks;

    -- Statistiques par projet
    SELECT 
        p.id,
        p.title,
        COUNT(t.id) as total_tasks,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        ROUND(AVG(t.completion_percentage), 2) as avg_completion
    FROM projects p
    LEFT JOIN tasks t ON p.id = t.project_id
    WHERE p.created_by = p_user_id OR t.assigned_to = p_user_id
    GROUP BY p.id;
END //

-- Gestion des Accès --

CREATE PROCEDURE sp_check_user_permission(
    IN p_user_id INT,
    IN p_project_id INT,
    OUT p_has_access BOOLEAN
)
BEGIN
    DECLARE user_role VARCHAR(20);
    
    SELECT role INTO user_role 
    FROM users 
    WHERE id = p_user_id;

    IF user_role = 'admin' THEN
        SET p_has_access = TRUE;
    ELSE
        SELECT EXISTS(
            SELECT 1 FROM project_members 
            WHERE project_id = p_project_id 
            AND user_id = p_user_id
        ) INTO p_has_access;
    END IF;
END //

-- Suivi d'Avancement --

CREATE PROCEDURE sp_update_task_status(
    IN p_task_id INT,
    IN p_status VARCHAR(20),
    IN p_completion_percentage DECIMAL(5,2)
)
BEGIN
    DECLARE p_project_id INT;
    
    -- Mise à jour de la tâche
    UPDATE tasks 
    SET status = p_status,
        completion_percentage = p_completion_percentage,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_task_id;

    -- Récupération du projet associé
    SELECT project_id INTO p_project_id 
    FROM tasks 
    WHERE id = p_task_id;

    -- Mise à jour de l'avancement du projet
    CALL sp_update_project_progress(p_project_id);
END //

-- Notifications et Activités --

CREATE PROCEDURE sp_log_activity(
    IN p_user_id INT,
    IN p_action_type VARCHAR(50),
    IN p_entity_type VARCHAR(50),
    IN p_entity_id INT,
    IN p_description TEXT
)
BEGIN
    INSERT INTO activity_logs (
        user_id, action_type, entity_type, 
        entity_id, description
    )
    VALUES (
        p_user_id, p_action_type, p_entity_type, 
        p_entity_id, p_description
    );
END //

DELIMITER ;
