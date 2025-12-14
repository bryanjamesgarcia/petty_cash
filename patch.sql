-- Check if 'email' column exists in 'users' table, and add it if missing
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(100) UNIQUE NULL;

-- Also ensure email_verified column exists just in case
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0;

-- Add verification_token column if missing (without IF NOT EXISTS for compatibility)
ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL;
