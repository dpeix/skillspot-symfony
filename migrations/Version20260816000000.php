<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enforce SkillSpot states, session modality and booking capacity in PostgreSQL.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE organizer_application ADD CONSTRAINT valid_organizer_application_status CHECK (status IN ('pending', 'approved', 'rejected'))");
        $this->addSql("ALTER TABLE workshop ADD CONSTRAINT valid_workshop_status CHECK (status IN ('draft', 'published', 'archived'))");
        $this->addSql("ALTER TABLE workshop ADD CONSTRAINT valid_workshop_category CHECK (category IN ('development', 'design', 'data', 'product', 'career'))");
        $this->addSql("ALTER TABLE workshop ADD CONSTRAINT valid_workshop_level CHECK (level IN ('beginner', 'intermediate', 'advanced'))");
        $this->addSql("ALTER TABLE workshop_session ADD CONSTRAINT valid_session_status CHECK (status IN ('scheduled', 'cancelled', 'completed'))");
        $this->addSql("ALTER TABLE workshop_session ADD CONSTRAINT valid_session_mode CHECK (mode IN ('onsite', 'online'))");
        $this->addSql("ALTER TABLE workshop_session ADD CONSTRAINT valid_session_details CHECK ((mode = 'onsite' AND NULLIF(BTRIM(location), '') IS NOT NULL AND meeting_url IS NULL) OR (mode = 'online' AND NULLIF(BTRIM(meeting_url), '') IS NOT NULL AND location IS NULL))");
        $this->addSql("ALTER TABLE booking ADD CONSTRAINT valid_booking_status CHECK (status IN ('confirmed', 'waitlisted', 'cancelled'))");
        $this->addSql("ALTER TABLE booking ADD CONSTRAINT valid_attendance_status CHECK (attendance IN ('pending', 'attended', 'no_show'))");
        $this->addSql(<<<'SQL'
            CREATE FUNCTION skillspot_enforce_session_capacity() RETURNS trigger AS $$
            DECLARE
                session_capacity INTEGER;
                confirmed_bookings INTEGER;
            BEGIN
                IF NEW.status <> 'confirmed' THEN
                    RETURN NEW;
                END IF;

                SELECT capacity INTO session_capacity
                FROM workshop_session
                WHERE id = NEW.session_id
                FOR UPDATE;

                SELECT COUNT(*) INTO confirmed_bookings
                FROM booking
                WHERE session_id = NEW.session_id
                  AND status = 'confirmed'
                  AND (TG_OP = 'INSERT' OR id <> NEW.id);

                IF confirmed_bookings >= session_capacity THEN
                    RAISE EXCEPTION 'Session capacity exceeded'
                        USING ERRCODE = '23514', CONSTRAINT = 'booking_within_session_capacity';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
            SQL);
        $this->addSql('CREATE TRIGGER booking_capacity_guard BEFORE INSERT OR UPDATE OF status, session_id ON booking FOR EACH ROW EXECUTE FUNCTION skillspot_enforce_session_capacity()');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER booking_capacity_guard ON booking');
        $this->addSql('DROP FUNCTION skillspot_enforce_session_capacity()');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT valid_attendance_status');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT valid_booking_status');
        $this->addSql('ALTER TABLE workshop_session DROP CONSTRAINT valid_session_details');
        $this->addSql('ALTER TABLE workshop_session DROP CONSTRAINT valid_session_mode');
        $this->addSql('ALTER TABLE workshop_session DROP CONSTRAINT valid_session_status');
        $this->addSql('ALTER TABLE workshop DROP CONSTRAINT valid_workshop_level');
        $this->addSql('ALTER TABLE workshop DROP CONSTRAINT valid_workshop_category');
        $this->addSql('ALTER TABLE workshop DROP CONSTRAINT valid_workshop_status');
        $this->addSql('ALTER TABLE organizer_application DROP CONSTRAINT valid_organizer_application_status');
    }
}
