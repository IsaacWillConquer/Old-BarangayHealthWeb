-- =========================================================
-- MIGRATION 001: Barangay Health Portal schema additions
-- Run this AFTER barangay_survey_schema.sql (base schema).
-- Additive only — safe on existing data, nothing dropped.
-- =========================================================

USE barangay_survey_db;

-- ---------------------------------------------------------
-- 1. RESIDENTS — extended personal/contact/emergency fields
-- ---------------------------------------------------------
ALTER TABLE residents
  ADD COLUMN MiddleName       VARCHAR(100) NULL AFTER FirstName,
  ADD COLUMN ExtensionName    VARCHAR(20)  NULL AFTER LastName,
  ADD COLUMN CivilStatus      ENUM('Single','Married','Widowed','Separated','Divorced') NULL AFTER ExtensionName,
  ADD COLUMN Gender           ENUM('Male','Female','Other') NULL AFTER CivilStatus,
  ADD COLUMN Address2         VARCHAR(255) NULL AFTER Address,
  ADD COLUMN Email            VARCHAR(150) NULL AFTER Address2,
  ADD COLUMN HomePhone        VARCHAR(20)  NULL AFTER ContactNumber,
  ADD COLUMN EmergencyContactName         VARCHAR(150) NULL AFTER HomePhone,
  ADD COLUMN EmergencyContactRelationship VARCHAR(100) NULL AFTER EmergencyContactName,
  ADD COLUMN EmergencyContactNumber       VARCHAR(20)  NULL AFTER EmergencyContactRelationship,
  ADD COLUMN Birthday         DATE NULL AFTER EmergencyContactNumber,
  ADD COLUMN Bio              TEXT NULL AFTER Birthday,
  ADD COLUMN Occupation       VARCHAR(150) NULL AFTER Bio,
  ADD COLUMN OccupationLocation VARCHAR(255) NULL AFTER Occupation,
  ADD COLUMN IsArchived       TINYINT(1) NOT NULL DEFAULT 0 AFTER Status,
  ADD COLUMN ArchivedAt       DATETIME NULL AFTER IsArchived,
  ADD COLUMN ArchivedBy       INT NULL AFTER ArchivedAt,
  ADD CONSTRAINT fk_resident_archived_by FOREIGN KEY (ArchivedBy) REFERENCES staffadmin(StaffID) ON DELETE SET NULL;

-- ---------------------------------------------------------
-- 2. STAFFADMIN — same soft-delete/archive treatment
-- ---------------------------------------------------------
ALTER TABLE staffadmin
  ADD COLUMN IsArchived TINYINT(1) NOT NULL DEFAULT 0 AFTER Status,
  ADD COLUMN ArchivedAt DATETIME NULL AFTER IsArchived,
  ADD COLUMN ArchivedBy INT NULL AFTER ArchivedAt,
  ADD CONSTRAINT fk_staff_archived_by FOREIGN KEY (ArchivedBy) REFERENCES staffadmin(StaffID) ON DELETE SET NULL;

-- ---------------------------------------------------------
-- 3. SPOUSE (1-to-1, only relevant if CivilStatus = Married)
-- ---------------------------------------------------------
CREATE TABLE resident_spouse (
    ResidentID        INT PRIMARY KEY,
    SpouseName        VARCHAR(150) NOT NULL,
    SpouseBirthdate   DATE NULL,
    SpouseCivilStatus ENUM('Single','Married','Widowed','Separated','Divorced') NULL,
    SpouseStatus      ENUM('Alive','Deceased') NOT NULL DEFAULT 'Alive',
    CONSTRAINT fk_spouse_resident FOREIGN KEY (ResidentID) REFERENCES residents(ResidentID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 4. CHILDREN (1-to-many)
-- ---------------------------------------------------------
CREATE TABLE resident_children (
    ChildID     INT AUTO_INCREMENT PRIMARY KEY,
    ResidentID  INT NOT NULL,
    LastName    VARCHAR(100) NOT NULL,
    FirstName   VARCHAR(100) NOT NULL,
    Birthdate   DATE NOT NULL,
    Gender      ENUM('Male','Female','Other') NULL,
    Status      ENUM('Living','Deceased') NOT NULL DEFAULT 'Living',
    CONSTRAINT fk_child_resident FOREIGN KEY (ResidentID) REFERENCES residents(ResidentID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 5. HEALTH EVENTS (staff-created barangay health activities)
-- ---------------------------------------------------------
CREATE TABLE health_events (
    EventID     INT AUTO_INCREMENT PRIMARY KEY,
    Title       VARCHAR(200) NOT NULL,
    EventType   ENUM('Checkup','VaccinationHuman','VaccinationDog','Cleanup','Wellness',
                      'BloodDonation','EyeConsult','Campaign','FreeVitamins','Other') NOT NULL,
    Description TEXT NULL,
    Location    VARCHAR(255) NOT NULL,
    EventDate   DATE NOT NULL,
    StartTime   TIME NULL,
    EndTime     TIME NULL,
    Status      ENUM('Draft','Upcoming','Active','Closed','Cancelled') NOT NULL DEFAULT 'Draft',
    ImagePath   VARCHAR(255) NULL,
    CreatedBy   INT NOT NULL,
    CreatedAt   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    IsArchived  TINYINT(1) NOT NULL DEFAULT 0,
    ArchivedAt  DATETIME NULL,
    ArchivedBy  INT NULL,
    CONSTRAINT fk_event_created_by  FOREIGN KEY (CreatedBy) REFERENCES staffadmin(StaffID) ON DELETE RESTRICT,
    CONSTRAINT fk_event_archived_by FOREIGN KEY (ArchivedBy) REFERENCES staffadmin(StaffID) ON DELETE SET NULL,
    CONSTRAINT chk_event_times CHECK (StartTime IS NULL OR EndTime IS NULL OR EndTime > StartTime)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 6. EVENT SIGN-UPS (optional resident participation)
-- ---------------------------------------------------------
CREATE TABLE event_signups (
    SignupID    INT AUTO_INCREMENT PRIMARY KEY,
    EventID     INT NOT NULL,
    ResidentID  INT NOT NULL,
    SignedUpAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_signup_event    FOREIGN KEY (EventID) REFERENCES health_events(EventID) ON DELETE CASCADE,
    CONSTRAINT fk_signup_resident FOREIGN KEY (ResidentID) REFERENCES residents(ResidentID) ON DELETE CASCADE,
    CONSTRAINT uq_one_signup_per_resident UNIQUE (EventID, ResidentID)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 7. HEALTH HISTORY (hybrid: common condition list + Other)
--    Never hard-deleted — soft-void with attribution.
-- ---------------------------------------------------------
CREATE TABLE resident_health_history (
    RecordID     INT AUTO_INCREMENT PRIMARY KEY,
    ResidentID   INT NOT NULL,
    ConditionType ENUM('Hypertension','Diabetes','Asthma','Tuberculosis','Dengue','HeartDisease',
                        'Allergy','PregnancyRelated','COVID19','Malnutrition','Disability',
                        'SeniorCareConcern','Other') NOT NULL,
    OtherConditionText VARCHAR(255) NULL,
    RecordDate   DATE NOT NULL,
    Status       ENUM('Ongoing','Resolved','Chronic','Monitoring') NOT NULL DEFAULT 'Ongoing',
    Notes        TEXT NULL,
    RecordedByStaffID INT NOT NULL,
    CreatedAt    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    IsVoided     TINYINT(1) NOT NULL DEFAULT 0,
    VoidedAt     DATETIME NULL,
    VoidedBy     INT NULL,
    CONSTRAINT fk_health_resident    FOREIGN KEY (ResidentID) REFERENCES residents(ResidentID) ON DELETE CASCADE,
    CONSTRAINT fk_health_recorded_by FOREIGN KEY (RecordedByStaffID) REFERENCES staffadmin(StaffID) ON DELETE RESTRICT,
    CONSTRAINT fk_health_voided_by   FOREIGN KEY (VoidedBy) REFERENCES staffadmin(StaffID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Indexes for the new lookups/filters/reports
-- ---------------------------------------------------------
CREATE INDEX idx_residents_archived        ON residents (IsArchived);
CREATE INDEX idx_staffadmin_archived       ON staffadmin (IsArchived);
CREATE INDEX idx_children_resident         ON resident_children (ResidentID);
CREATE INDEX idx_events_status             ON health_events (Status);
CREATE INDEX idx_events_date               ON health_events (EventDate);
CREATE INDEX idx_events_archived           ON health_events (IsArchived);
CREATE INDEX idx_signups_event             ON event_signups (EventID);
CREATE INDEX idx_signups_resident          ON event_signups (ResidentID);
CREATE INDEX idx_health_history_resident   ON resident_health_history (ResidentID);
CREATE INDEX idx_health_history_date       ON resident_health_history (RecordDate);
CREATE INDEX idx_health_history_voided     ON resident_health_history (IsVoided);
