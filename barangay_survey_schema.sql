
CREATE DATABASE IF NOT EXISTS barangay_survey_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE barangay_survey_db;

CREATE TABLE staffadmin (
    StaffID         INT AUTO_INCREMENT PRIMARY KEY,
    FirstName       VARCHAR(100)    NOT NULL,
    LastName        VARCHAR(100)    NOT NULL,
    Email           VARCHAR(150)    NOT NULL UNIQUE,
    Password        VARCHAR(255)    NOT NULL,
    Role            ENUM('Admin', 'Staff') NOT NULL DEFAULT 'Staff',
    DefaultPassword TINYINT(1)      NOT NULL DEFAULT 1,
    Status          ENUM('Active', 'Deactivated') NOT NULL DEFAULT 'Active',
    ProfileImage    VARCHAR(255)    NULL,
    CreatedAt       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE residents (
    ResidentID      INT AUTO_INCREMENT PRIMARY KEY,
    ResidentNumber  VARCHAR(50)     NOT NULL UNIQUE,
    Password        VARCHAR(255)    NOT NULL,
    DefaultPassword TINYINT(1)      NOT NULL DEFAULT 1,
    FirstName       VARCHAR(100)    NOT NULL,
    LastName        VARCHAR(100)    NOT NULL,
    ContactNumber   VARCHAR(20)     NULL,
    Address         VARCHAR(255)    NULL,
    ProfileImage    VARCHAR(255)    NULL,
    Status          ENUM('Active', 'Suspended', 'Inactive') NOT NULL DEFAULT 'Active',
    CreatedAt       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE surveys (
    SurveyID        INT AUTO_INCREMENT PRIMARY KEY,
    Title           VARCHAR(200)    NOT NULL,
    Description     TEXT            NULL,
    Status          ENUM('Draft', 'Active', 'Closed', 'Archived') NOT NULL DEFAULT 'Draft',
    OpenAt          DATETIME        NULL,
    CloseAt         DATETIME        NULL,
    CreatedBy       INT             NOT NULL,
    CreatedAt       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_surveys_staff FOREIGN KEY (CreatedBy) REFERENCES staffadmin(StaffID) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE survey_questions (
    QuestionID      INT AUTO_INCREMENT PRIMARY KEY,
    SurveyID        INT             NOT NULL,
    QuestionText    VARCHAR(500)    NOT NULL,
    Type            ENUM('MultipleChoice', 'YesNo', 'Rating', 'ShortAnswer') NOT NULL,
    IsRequired      TINYINT(1)      NOT NULL DEFAULT 1,
    OrderIndex      INT             NOT NULL DEFAULT 0,
    RatingMin       INT             NULL,
    RatingMax       INT             NULL,
    RatingStep      INT             NOT NULL DEFAULT 1,
    CONSTRAINT fk_questions_survey FOREIGN KEY (SurveyID) REFERENCES surveys(SurveyID) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE survey_choices (
    ChoiceID        INT AUTO_INCREMENT PRIMARY KEY,
    QuestionID      INT             NOT NULL,
    ChoiceText      VARCHAR(255)    NOT NULL,
    OrderIndex      INT             NOT NULL DEFAULT 0,
    CONSTRAINT fk_choices_question FOREIGN KEY (QuestionID) REFERENCES survey_questions(QuestionID) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE responses (
    ResponseID      INT AUTO_INCREMENT PRIMARY KEY,
    SurveyID        INT             NOT NULL,
    ResidentID      INT             NOT NULL,
    SubmittedAt     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_responses_survey FOREIGN KEY (SurveyID) REFERENCES surveys(SurveyID) ON DELETE CASCADE,
    CONSTRAINT fk_responses_resident FOREIGN KEY (ResidentID) REFERENCES residents(ResidentID) ON DELETE CASCADE,
    CONSTRAINT uq_one_response_per_resident UNIQUE (SurveyID, ResidentID)
) ENGINE=InnoDB;

CREATE TABLE response_answers (
    AnswerID          INT AUTO_INCREMENT PRIMARY KEY,
    ResponseID        INT             NOT NULL,
    QuestionID        INT             NOT NULL,
    AnswerText        TEXT            NULL,
    SelectedChoiceID  INT             NULL,
    CONSTRAINT fk_answers_response FOREIGN KEY (ResponseID) REFERENCES responses(ResponseID) ON DELETE CASCADE,
    CONSTRAINT fk_answers_question FOREIGN KEY (QuestionID) REFERENCES survey_questions(QuestionID) ON DELETE CASCADE,
    CONSTRAINT fk_answers_choice FOREIGN KEY (SelectedChoiceID) REFERENCES survey_choices(ChoiceID) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE logs (
    LogID           INT AUTO_INCREMENT PRIMARY KEY,
    ActorType       ENUM('StaffAdmin', 'Resident') NOT NULL,
    ActorID         INT             NOT NULL,
    Action          VARCHAR(255)    NOT NULL,
    TargetType      VARCHAR(100)    NULL,
    TargetID        INT             NULL,
    Timestamp       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    IPAddress       VARCHAR(45)     NULL
) ENGINE=InnoDB;

CREATE VIEW survey_results AS
SELECT
    sq.SurveyID,
    ra.QuestionID,
    sq.Type,
    COALESCE(sc.ChoiceText, ra.AnswerText) AS AnswerValue,
    COUNT(*) AS ResponseCount
FROM response_answers ra
JOIN survey_questions sq ON sq.QuestionID = ra.QuestionID
LEFT JOIN survey_choices sc ON sc.ChoiceID = ra.SelectedChoiceID
GROUP BY sq.SurveyID, ra.QuestionID, sq.Type, AnswerValue;

CREATE INDEX idx_surveys_status     ON surveys (Status);
CREATE INDEX idx_questions_survey   ON survey_questions (SurveyID);
CREATE INDEX idx_choices_question   ON survey_choices (QuestionID);
CREATE INDEX idx_responses_survey   ON responses (SurveyID);
CREATE INDEX idx_responses_resident ON responses (ResidentID);
CREATE INDEX idx_answers_response   ON response_answers (ResponseID);
CREATE INDEX idx_logs_actor         ON logs (ActorType, ActorID);

INSERT INTO staffadmin (FirstName, LastName, Email, Password, Role, DefaultPassword)
VALUES ('System', 'Admin', 'admin@barangay.gov', '$2b$10$4GO1ih.HY5WjTIPrWjHNrud4TsJB9Csaz.r6jRwPXTw/vL4L0dOJO', 'Admin', 1);

INSERT INTO staffadmin (FirstName, LastName, Email, Password, Role, DefaultPassword)
VALUES ('Health', 'Staff', 'staff@barangay.gov', '$2b$10$4GO1ih.HY5WjTIPrWjHNrud4TsJB9Csaz.r6jRwPXTw/vL4L0dOJO', 'Staff', 1);

INSERT INTO residents (ResidentNumber, Password, DefaultPassword, FirstName, LastName, ContactNumber, Address)
VALUES ('RES-0001', '$2b$10$zZaTDkr4LELupEMNpVh0iOK62TcaX6uTNpokueyoftbRoK6k2ypkO', 1, 'Juan', 'Dela Cruz', '09171234567', 'Purok 1, Barangay Health Center');
