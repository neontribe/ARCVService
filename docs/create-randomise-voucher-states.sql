DELIMITER //
CREATE PROCEDURE UpdateRandomTimestamps()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE pk_var INT;
    DECLARE random_timestamp TIMESTAMP;

    DECLARE cur CURSOR FOR
    SELECT id FROM voucher_states;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO pk_var;
        IF done THEN
            LEAVE read_loop;
        END IF;

        SET random_timestamp = TIMESTAMPADD(SECOND, 
            FLOOR(RAND() * 
                TIMESTAMPDIFF(SECOND, '2023-01-01', NOW())),
            '2023-01-01');

        UPDATE voucher_states
        SET created_at = random_timestamp
        WHERE id = pk_var;
    END LOOP;

    CLOSE cur;
END //
DELIMITER ;

CALL UpdateRandomTimestamps();