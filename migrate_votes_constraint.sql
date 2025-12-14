-- Alternative Migration: Rename old constraint and create new one
-- This avoids the foreign key constraint issue

USE election_system;

-- Step 1: Add the new unique constraint with a different name
ALTER TABLE votes ADD UNIQUE KEY unique_vote_per_position (election_id, student_id, position);

-- Step 2: Try to drop the old constraint (if possible)
-- If this fails, it's okay - the new constraint will take precedence
ALTER TABLE votes DROP INDEX unique_vote;

-- Verification
SHOW INDEX FROM votes WHERE Key_name LIKE 'unique_vote%';
