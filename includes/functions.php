<?php
// includes/functions.php

/**
 * Verify a student based on Student ID and Full Name.
 *
 * @param PDO $pdo
 * @param string $student_id
 * @param string $full_name
 * @return bool
 */
function verifyVoter($pdo, $student_id, $full_name)
{
    if (empty($student_id) || empty($full_name)) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM voters WHERE student_id = ? AND full_name = ?");
    $stmt->execute([$student_id, $full_name]);

    return $stmt->fetchColumn() > 0;
}

/**
 * Check if a student has already voted in a specific election.
 *
 * @param PDO $pdo
 * @param int $election_id
 * @param string $student_id
 * @return bool
 */
function hasVoted($pdo, $election_id, $student_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id = ? AND student_id = ?");
    $stmt->execute([$election_id, $student_id]);

    return $stmt->fetchColumn() > 0;
}

/**
 * Check if a student has already voted for a specific position in an election.
 *
 * @param PDO $pdo
 * @param int $election_id
 * @param string $student_id
 * @param string $position
 * @return bool
 */
function hasVotedForPosition($pdo, $election_id, $student_id, $position)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id = ? AND student_id = ? AND position = ?");
    $stmt->execute([$election_id, $student_id, $position]);

    return $stmt->fetchColumn() > 0;
}

/**
 * Generate a unique tracking code.
 *
 * @return string
 */
function generateTrackingCode()
{
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
}

/**
 * Helper to sanitize input.
 */
function sanitizeInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}
?>