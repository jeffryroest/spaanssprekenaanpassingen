-- Spaansspreken.nl canonical schema
-- Target: MySQL 8.0.16+ / Laravel. All application timestamps are UTC.
-- This file defines domain tables; Laravel framework tables can be added by migrations.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('pending', 'active', 'suspended', 'anonymized') NOT NULL DEFAULT 'pending',
    email_verified_at DATETIME(6) NULL,
    last_login_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    deleted_at DATETIME(6) NULL,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_status_deleted (status, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    label VARCHAR(100) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_roles_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE role_user (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    assigned_at DATETIME(6) NOT NULL,
    assigned_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (user_id, role_id),
    KEY idx_role_user_role (role_id, user_id),
    KEY idx_role_user_assigner (assigned_by),
    CONSTRAINT fk_role_user_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_role_user_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT fk_role_user_assigner FOREIGN KEY (assigned_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE user_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    display_name VARCHAR(80) NULL,
    ui_locale VARCHAR(10) NOT NULL DEFAULT 'nl-NL',
    target_locale VARCHAR(10) NOT NULL DEFAULT 'es-ES',
    timezone VARCHAR(64) NOT NULL DEFAULT 'Europe/Madrid',
    cefr_level ENUM('pre_a1', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2') NOT NULL DEFAULT 'pre_a1',
    daily_goal_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    speaking_feedback_level ENUM('supportive', 'balanced', 'strict') NOT NULL DEFAULT 'balanced',
    preferences JSON NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT chk_user_profiles_goal CHECK (daily_goal_minutes BETWEEN 1 AND 240)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Content Studio: canonical publication envelope and immutable revision history.
CREATE TABLE content_nodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(40) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    status ENUM(
        'draft',
        'in_review',
        'changes_requested',
        'approved',
        'scheduled',
        'published',
        'withdrawn',
        'archived'
    ) NOT NULL DEFAULT 'draft',
    default_locale VARCHAR(10) NOT NULL DEFAULT 'es-ES',
    schema_version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    current_version INT UNSIGNED NOT NULL DEFAULT 1,
    published_at DATETIME(6) NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    deleted_at DATETIME(6) NULL,
    UNIQUE KEY uq_content_nodes_type_slug (content_type, slug),
    KEY idx_content_nodes_work_queue (status, content_type, updated_at),
    KEY idx_content_nodes_published (content_type, published_at),
    KEY idx_content_nodes_created_by (created_by),
    KEY idx_content_nodes_updated_by (updated_by),
    CONSTRAINT fk_content_nodes_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_content_nodes_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_content_nodes_publish CHECK (
        (status = 'published' AND published_at IS NOT NULL) OR status <> 'published'
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE content_localizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_node_id BIGINT UNSIGNED NOT NULL,
    locale VARCHAR(10) NOT NULL,
    title VARCHAR(255) NULL,
    summary TEXT NULL,
    body LONGTEXT NULL,
    metadata JSON NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_content_localizations_node_locale (content_node_id, locale),
    KEY idx_content_localizations_locale_title (locale, title),
    CONSTRAINT fk_content_localizations_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE content_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_node_id BIGINT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'rejected', 'superseded') NOT NULL DEFAULT 'draft',
    snapshot JSON NOT NULL,
    change_summary VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_content_revisions_node_version (content_node_id, version),
    KEY idx_content_revisions_review_queue (status, created_at),
    KEY idx_content_revisions_created_by (created_by),
    KEY idx_content_revisions_reviewed_by (reviewed_by),
    CONSTRAINT fk_content_revisions_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_content_revisions_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_content_revisions_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE content_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_node_id BIGINT UNSIGNED NOT NULL,
    content_revision_id BIGINT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL,
    action VARCHAR(32) NOT NULL,
    from_status VARCHAR(32) NOT NULL,
    to_status VARCHAR(32) NOT NULL,
    note TEXT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    actor_role VARCHAR(32) NULL,
    created_at DATETIME(6) NOT NULL,
    KEY idx_content_reviews_version_history (content_node_id, version, created_at),
    KEY idx_content_reviews_action_queue (action, created_at),
    CONSTRAINT fk_content_reviews_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_content_reviews_revision FOREIGN KEY (content_revision_id) REFERENCES content_revisions (id) ON DELETE CASCADE,
    CONSTRAINT fk_content_reviews_actor FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE content_releases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    description TEXT NULL,
    target_channel VARCHAR(24) NOT NULL DEFAULT 'preview',
    desired_publish_at DATETIME(6) NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'draft',
    owner_user_id BIGINT UNSIGNED NULL,
    published_by BIGINT UNSIGNED NULL,
    published_at DATETIME(6) NULL,
    cancelled_by BIGINT UNSIGNED NULL,
    cancelled_at DATETIME(6) NULL,
    cancellation_reason TEXT NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    KEY idx_content_releases_planning (status, desired_publish_at),
    KEY idx_content_releases_channel (target_channel, published_at),
    CONSTRAINT fk_content_releases_owner FOREIGN KEY (owner_user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_content_releases_publisher FOREIGN KEY (published_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_content_releases_canceller FOREIGN KEY (cancelled_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE content_release_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_release_id BIGINT UNSIGNED NOT NULL,
    content_node_id BIGINT UNSIGNED NOT NULL,
    content_revision_id BIGINT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_content_release_items_node (content_release_id, content_node_id),
    KEY idx_content_release_items_version (content_node_id, version),
    CONSTRAINT fk_content_release_items_release FOREIGN KEY (content_release_id) REFERENCES content_releases (id) ON DELETE CASCADE,
    CONSTRAINT fk_content_release_items_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE RESTRICT,
    CONSTRAINT fk_content_release_items_revision FOREIGN KEY (content_revision_id) REFERENCES content_revisions (id) ON DELETE RESTRICT,
    CONSTRAINT fk_content_release_items_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE content_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    source_type ENUM('csv', 'json', 'api', 'manual', 'legacy_export') NOT NULL,
    origin_uri VARCHAR(1000) NULL,
    license_note VARCHAR(500) NULL,
    attribution_note VARCHAR(500) NULL,
    configuration JSON NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    deleted_at DATETIME(6) NULL,
    UNIQUE KEY uq_content_sources_name (name),
    KEY idx_content_sources_active (active, deleted_at),
    KEY idx_content_sources_created_by (created_by),
    CONSTRAINT fk_content_sources_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE import_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_source_id BIGINT UNSIGNED NOT NULL,
    original_filename VARCHAR(255) NULL,
    file_checksum CHAR(64) NULL,
    status ENUM(
        'uploaded',
        'analyzed',
        'mapping_required',
        'validation_required',
        'decisions_required',
        'ready_for_staging',
        'staged',
        'partially_staged',
        'completed',
        'failed',
        'cancelled'
    ) NOT NULL DEFAULT 'uploaded',
    mapping_definition JSON NULL,
    row_count INT UNSIGNED NOT NULL DEFAULT 0,
    accepted_count INT UNSIGNED NOT NULL DEFAULT 0,
    rejected_count INT UNSIGNED NOT NULL DEFAULT 0,
    error_summary JSON NULL,
    imported_by BIGINT UNSIGNED NULL,
    started_at DATETIME(6) NULL,
    completed_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    KEY idx_import_batches_source_created (content_source_id, created_at),
    KEY idx_import_batches_status (status, created_at),
    KEY idx_import_batches_imported_by (imported_by),
    CONSTRAINT fk_import_batches_source FOREIGN KEY (content_source_id) REFERENCES content_sources (id) ON DELETE RESTRICT,
    CONSTRAINT fk_import_batches_imported_by FOREIGN KEY (imported_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE import_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_batch_id BIGINT UNSIGNED NOT NULL,
    row_number INT UNSIGNED NOT NULL,
    external_key VARCHAR(255) NULL,
    source_checksum CHAR(64) NOT NULL,
    raw_payload JSON NOT NULL,
    normalized_payload JSON NULL,
    validation_errors JSON NULL,
    validation_status ENUM('pending', 'valid', 'warning', 'invalid', 'possible_duplicate') NOT NULL DEFAULT 'pending',
    review_status ENUM('pending', 'needs_review', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    lifecycle_status ENUM('pending', 'staged', 'promoted', 'skipped', 'deleted') NOT NULL DEFAULT 'pending',
    proposed_content_status ENUM('draft') NOT NULL DEFAULT 'draft',
    resulting_content_node_id BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_import_records_batch_row (import_batch_id, row_number),
    UNIQUE KEY uq_import_records_batch_checksum (import_batch_id, source_checksum),
    KEY idx_import_records_validation_queue (validation_status, import_batch_id, row_number),
    KEY idx_import_records_review_queue (review_status, import_batch_id, row_number),
    KEY idx_import_records_lifecycle (lifecycle_status, import_batch_id, row_number),
    KEY idx_import_records_external_key (external_key),
    KEY idx_import_records_result (resulting_content_node_id),
    KEY idx_import_records_reviewer (reviewed_by),
    CONSTRAINT fk_import_records_batch FOREIGN KEY (import_batch_id) REFERENCES import_batches (id) ON DELETE CASCADE,
    CONSTRAINT fk_import_records_result FOREIGN KEY (resulting_content_node_id) REFERENCES content_nodes (id) ON DELETE SET NULL,
    CONSTRAINT fk_import_records_reviewer FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE media_assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kind ENUM('image', 'audio', 'video', 'document') NOT NULL,
    storage_disk VARCHAR(60) NOT NULL,
    object_key VARCHAR(700) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    byte_size BIGINT UNSIGNED NOT NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    width_px INT UNSIGNED NULL,
    height_px INT UNSIGNED NULL,
    duration_ms INT UNSIGNED NULL,
    alt_text VARCHAR(500) NULL,
    attribution JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    deleted_at DATETIME(6) NULL,
    UNIQUE KEY uq_media_assets_object (storage_disk, object_key),
    KEY idx_media_assets_kind_deleted (kind, deleted_at),
    KEY idx_media_assets_checksum (checksum_sha256),
    KEY idx_media_assets_created_by (created_by),
    CONSTRAINT fk_media_assets_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE content_media (
    content_node_id BIGINT UNSIGNED NOT NULL,
    media_asset_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(50) NOT NULL,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    metadata JSON NULL,
    PRIMARY KEY (content_node_id, media_asset_id, role),
    KEY idx_content_media_asset (media_asset_id),
    KEY idx_content_media_order (content_node_id, role, position),
    CONSTRAINT fk_content_media_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_content_media_asset FOREIGN KEY (media_asset_id) REFERENCES media_assets (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    namespace VARCHAR(50) NOT NULL DEFAULT 'general',
    slug VARCHAR(100) NOT NULL,
    label VARCHAR(160) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_tags_namespace_slug (namespace, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE content_tag (
    content_node_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (content_node_id, tag_id),
    KEY idx_content_tag_tag (tag_id, content_node_id),
    CONSTRAINT fk_content_tag_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_content_tag_tag FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE content_relations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_content_node_id BIGINT UNSIGNED NOT NULL,
    target_content_node_id BIGINT UNSIGNED NOT NULL,
    relation_type VARCHAR(60) NOT NULL,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    metadata JSON NULL,
    created_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_content_relations_pair (source_content_node_id, target_content_node_id, relation_type),
    KEY idx_content_relations_target (target_content_node_id, relation_type),
    KEY idx_content_relations_order (source_content_node_id, relation_type, position),
    CONSTRAINT fk_content_relations_source FOREIGN KEY (source_content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_content_relations_target FOREIGN KEY (target_content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT chk_content_relations_self CHECK (source_content_node_id <> target_content_node_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Language-learning content specializations. Each PK is also a content_nodes FK.
CREATE TABLE lexemes (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    lemma VARCHAR(255) NOT NULL,
    normalized_lemma VARCHAR(255) NOT NULL,
    part_of_speech ENUM('noun', 'verb', 'adjective', 'adverb', 'pronoun', 'preposition', 'conjunction', 'interjection', 'article', 'phrase', 'other') NOT NULL,
    grammatical_gender ENUM('masculine', 'feminine', 'common', 'neutral', 'not_applicable') NOT NULL DEFAULT 'not_applicable',
    grammatical_number ENUM('singular', 'plural', 'invariant', 'not_applicable') NOT NULL DEFAULT 'not_applicable',
    article VARCHAR(20) NULL,
    cefr_level ENUM('pre_a1', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2') NOT NULL DEFAULT 'pre_a1',
    pronunciation_ipa VARCHAR(255) NULL,
    pronunciation_hint VARCHAR(255) NULL,
    inflection_data JSON NULL,
    usage_notes TEXT NULL,
    KEY idx_lexemes_lemma (normalized_lemma),
    KEY idx_lexemes_level_pos (cefr_level, part_of_speech),
    CONSTRAINT fk_lexemes_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE lexeme_translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lexeme_content_node_id BIGINT UNSIGNED NOT NULL,
    locale VARCHAR(10) NOT NULL DEFAULT 'nl-NL',
    translation VARCHAR(500) NOT NULL,
    sense_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    register_code VARCHAR(40) NULL,
    notes TEXT NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_lexeme_translations_sense (lexeme_content_node_id, locale, sense_number),
    KEY idx_lexeme_translations_lookup (locale, translation),
    CONSTRAINT fk_lexeme_translations_lexeme FOREIGN KEY (lexeme_content_node_id) REFERENCES lexemes (content_node_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE phrases (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    text_es TEXT NOT NULL,
    normalized_text_es VARCHAR(700) NOT NULL,
    cefr_level ENUM('pre_a1', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2') NOT NULL DEFAULT 'pre_a1',
    communicative_function VARCHAR(100) NULL,
    register_code VARCHAR(40) NULL,
    pronunciation_hint TEXT NULL,
    KEY idx_phrases_level_function (cefr_level, communicative_function),
    KEY idx_phrases_normalized (normalized_text_es),
    CONSTRAINT fk_phrases_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE example_sentences (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    sentence_es TEXT NOT NULL,
    cefr_level ENUM('pre_a1', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2') NOT NULL DEFAULT 'pre_a1',
    translation_nl TEXT NULL,
    notes TEXT NULL,
    CONSTRAINT fk_example_sentences_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE grammar_topics (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    cefr_level ENUM('pre_a1', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2') NOT NULL,
    rule_data JSON NOT NULL,
    KEY idx_grammar_topics_level (cefr_level),
    CONSTRAINT fk_grammar_topics_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE exercises (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    exercise_type ENUM('multiple_choice', 'match', 'order', 'fill_blank', 'listen', 'speak', 'free_response') NOT NULL,
    cefr_level ENUM('pre_a1', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2') NOT NULL DEFAULT 'pre_a1',
    instructions JSON NOT NULL,
    scoring_config JSON NOT NULL,
    max_score DECIMAL(6,2) NOT NULL DEFAULT 100.00,
    estimated_seconds SMALLINT UNSIGNED NULL,
    KEY idx_exercises_type_level (exercise_type, cefr_level),
    CONSTRAINT fk_exercises_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT chk_exercises_score CHECK (max_score > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE exercise_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exercise_content_node_id BIGINT UNSIGNED NOT NULL,
    position SMALLINT UNSIGNED NOT NULL,
    prompt JSON NOT NULL,
    answer_spec JSON NOT NULL,
    feedback_spec JSON NULL,
    target_content_node_id BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_exercise_items_position (exercise_content_node_id, position),
    KEY idx_exercise_items_target (target_content_node_id),
    CONSTRAINT fk_exercise_items_exercise FOREIGN KEY (exercise_content_node_id) REFERENCES exercises (content_node_id) ON DELETE CASCADE,
    CONSTRAINT fk_exercise_items_target FOREIGN KEY (target_content_node_id) REFERENCES content_nodes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Spain game world and story content.
CREATE TABLE regions (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    parent_region_content_node_id BIGINT UNSIGNED NULL,
    region_code VARCHAR(30) NULL,
    map_geometry JSON NULL,
    unlock_rule JSON NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_regions_parent_order (parent_region_content_node_id, sort_order),
    CONSTRAINT fk_regions_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_regions_parent FOREIGN KEY (parent_region_content_node_id) REFERENCES regions (content_node_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE locations (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    region_content_node_id BIGINT UNSIGNED NOT NULL,
    location_type VARCHAR(50) NOT NULL,
    map_x DECIMAL(8,5) NULL,
    map_y DECIMAL(8,5) NULL,
    unlock_rule JSON NULL,
    ambient_config JSON NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_locations_region_order (region_content_node_id, sort_order),
    KEY idx_locations_type (location_type),
    CONSTRAINT fk_locations_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_locations_region FOREIGN KEY (region_content_node_id) REFERENCES regions (content_node_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE npcs (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    home_location_content_node_id BIGINT UNSIGNED NULL,
    persona JSON NOT NULL,
    voice_config JSON NULL,
    initial_trust SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_npcs_home (home_location_content_node_id),
    CONSTRAINT fk_npcs_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_npcs_home FOREIGN KEY (home_location_content_node_id) REFERENCES locations (content_node_id) ON DELETE SET NULL,
    CONSTRAINT chk_npcs_trust CHECK (initial_trust <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE item_definitions (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    item_type ENUM('collectible', 'cosmetic', 'quest', 'consumable', 'badge') NOT NULL,
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') NOT NULL DEFAULT 'common',
    stackable BOOLEAN NOT NULL DEFAULT FALSE,
    max_stack INT UNSIGNED NOT NULL DEFAULT 1,
    effects JSON NULL,
    KEY idx_item_definitions_type_rarity (item_type, rarity),
    CONSTRAINT fk_item_definitions_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT chk_item_definitions_stack CHECK (max_stack >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE conversation_scenarios (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    location_content_node_id BIGINT UNSIGNED NULL,
    primary_npc_content_node_id BIGINT UNSIGNED NULL,
    cefr_level ENUM('pre_a1', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2') NOT NULL DEFAULT 'pre_a1',
    mode ENUM('scripted', 'hybrid', 'generative') NOT NULL DEFAULT 'hybrid',
    objective_spec JSON NOT NULL,
    safety_policy_version VARCHAR(40) NOT NULL,
    prompt_template_key VARCHAR(120) NULL,
    conversation_config JSON NOT NULL,
    KEY idx_conversation_scenarios_location (location_content_node_id),
    KEY idx_conversation_scenarios_npc (primary_npc_content_node_id),
    KEY idx_conversation_scenarios_level_mode (cefr_level, mode),
    CONSTRAINT fk_conversation_scenarios_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_conversation_scenarios_location FOREIGN KEY (location_content_node_id) REFERENCES locations (content_node_id) ON DELETE SET NULL,
    CONSTRAINT fk_conversation_scenarios_npc FOREIGN KEY (primary_npc_content_node_id) REFERENCES npcs (content_node_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE conversation_nodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scenario_content_node_id BIGINT UNSIGNED NOT NULL,
    node_key VARCHAR(100) NOT NULL,
    speaker ENUM('npc', 'learner', 'system') NOT NULL,
    node_type ENUM('message', 'listen', 'speak', 'choice', 'evaluation', 'branch', 'end') NOT NULL,
    content_spec JSON NOT NULL,
    is_start BOOLEAN NOT NULL DEFAULT FALSE,
    is_terminal BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_conversation_nodes_scenario_key (scenario_content_node_id, node_key),
    KEY idx_conversation_nodes_start (scenario_content_node_id, is_start),
    CONSTRAINT fk_conversation_nodes_scenario FOREIGN KEY (scenario_content_node_id) REFERENCES conversation_scenarios (content_node_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE conversation_edges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scenario_content_node_id BIGINT UNSIGNED NOT NULL,
    from_node_id BIGINT UNSIGNED NOT NULL,
    to_node_id BIGINT UNSIGNED NOT NULL,
    priority SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    condition_spec JSON NULL,
    effect_spec JSON NULL,
    created_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_conversation_edges_route (from_node_id, to_node_id, priority),
    KEY idx_conversation_edges_scenario (scenario_content_node_id),
    KEY idx_conversation_edges_from_priority (from_node_id, priority),
    KEY idx_conversation_edges_to (to_node_id),
    CONSTRAINT fk_conversation_edges_scenario FOREIGN KEY (scenario_content_node_id) REFERENCES conversation_scenarios (content_node_id) ON DELETE CASCADE,
    CONSTRAINT fk_conversation_edges_from FOREIGN KEY (from_node_id) REFERENCES conversation_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_conversation_edges_to FOREIGN KEY (to_node_id) REFERENCES conversation_nodes (id) ON DELETE CASCADE,
    CONSTRAINT chk_conversation_edges_self CHECK (from_node_id <> to_node_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE conversation_intents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scenario_content_node_id BIGINT UNSIGNED NOT NULL,
    intent_key VARCHAR(100) NOT NULL,
    label VARCHAR(160) NOT NULL,
    examples JSON NULL,
    success_effect JSON NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_conversation_intents_scenario_key (scenario_content_node_id, intent_key),
    CONSTRAINT fk_conversation_intents_scenario FOREIGN KEY (scenario_content_node_id) REFERENCES conversation_scenarios (content_node_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE conversation_node_intents (
    conversation_node_id BIGINT UNSIGNED NOT NULL,
    conversation_intent_id BIGINT UNSIGNED NOT NULL,
    required BOOLEAN NOT NULL DEFAULT FALSE,
    weight DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    PRIMARY KEY (conversation_node_id, conversation_intent_id),
    KEY idx_conversation_node_intents_intent (conversation_intent_id),
    CONSTRAINT fk_conversation_node_intents_node FOREIGN KEY (conversation_node_id) REFERENCES conversation_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_conversation_node_intents_intent FOREIGN KEY (conversation_intent_id) REFERENCES conversation_intents (id) ON DELETE CASCADE,
    CONSTRAINT chk_conversation_node_intents_weight CHECK (weight >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE missions (
    content_node_id BIGINT UNSIGNED PRIMARY KEY,
    region_content_node_id BIGINT UNSIGNED NOT NULL,
    start_location_content_node_id BIGINT UNSIGNED NULL,
    mission_type ENUM('story', 'daily', 'practice', 'challenge', 'event') NOT NULL DEFAULT 'story',
    cefr_level ENUM('pre_a1', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2') NOT NULL DEFAULT 'pre_a1',
    required_confianza INT UNSIGNED NOT NULL DEFAULT 0,
    required_valentia INT UNSIGNED NOT NULL DEFAULT 0,
    base_xp INT UNSIGNED NOT NULL DEFAULT 0,
    unlock_rule JSON NULL,
    availability_rule JSON NULL,
    replayable BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_missions_region_order (region_content_node_id, sort_order),
    KEY idx_missions_type_level (mission_type, cefr_level),
    KEY idx_missions_start_location (start_location_content_node_id),
    CONSTRAINT fk_missions_node FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE CASCADE,
    CONSTRAINT fk_missions_region FOREIGN KEY (region_content_node_id) REFERENCES regions (content_node_id) ON DELETE RESTRICT,
    CONSTRAINT fk_missions_start_location FOREIGN KEY (start_location_content_node_id) REFERENCES locations (content_node_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE mission_steps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mission_content_node_id BIGINT UNSIGNED NOT NULL,
    step_key VARCHAR(100) NOT NULL,
    position SMALLINT UNSIGNED NOT NULL,
    step_type ENUM('narrative', 'travel', 'exercise', 'conversation', 'collect', 'checkpoint') NOT NULL,
    referenced_content_node_id BIGINT UNSIGNED NULL,
    objective_spec JSON NOT NULL,
    success_rule JSON NOT NULL,
    retry_policy JSON NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_mission_steps_key (mission_content_node_id, step_key),
    UNIQUE KEY uq_mission_steps_position (mission_content_node_id, position),
    KEY idx_mission_steps_reference (referenced_content_node_id),
    CONSTRAINT fk_mission_steps_mission FOREIGN KEY (mission_content_node_id) REFERENCES missions (content_node_id) ON DELETE CASCADE,
    CONSTRAINT fk_mission_steps_reference FOREIGN KEY (referenced_content_node_id) REFERENCES content_nodes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE mission_prerequisites (
    mission_content_node_id BIGINT UNSIGNED NOT NULL,
    prerequisite_mission_content_node_id BIGINT UNSIGNED NOT NULL,
    requirement_spec JSON NULL,
    PRIMARY KEY (mission_content_node_id, prerequisite_mission_content_node_id),
    KEY idx_mission_prerequisites_reverse (prerequisite_mission_content_node_id),
    CONSTRAINT fk_mission_prerequisites_mission FOREIGN KEY (mission_content_node_id) REFERENCES missions (content_node_id) ON DELETE CASCADE,
    CONSTRAINT fk_mission_prerequisites_required FOREIGN KEY (prerequisite_mission_content_node_id) REFERENCES missions (content_node_id) ON DELETE CASCADE,
    CONSTRAINT chk_mission_prerequisites_self CHECK (mission_content_node_id <> prerequisite_mission_content_node_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE mission_rewards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mission_content_node_id BIGINT UNSIGNED NOT NULL,
    reward_type ENUM('xp', 'confianza', 'valentia', 'coins', 'item') NOT NULL,
    item_content_node_id BIGINT UNSIGNED NULL,
    amount INT UNSIGNED NOT NULL,
    first_completion_only BOOLEAN NOT NULL DEFAULT TRUE,
    metadata JSON NULL,
    created_at DATETIME(6) NOT NULL,
    KEY idx_mission_rewards_mission (mission_content_node_id),
    KEY idx_mission_rewards_item (item_content_node_id),
    CONSTRAINT fk_mission_rewards_mission FOREIGN KEY (mission_content_node_id) REFERENCES missions (content_node_id) ON DELETE CASCADE,
    CONSTRAINT fk_mission_rewards_item FOREIGN KEY (item_content_node_id) REFERENCES item_definitions (content_node_id) ON DELETE RESTRICT,
    CONSTRAINT chk_mission_rewards_amount CHECK (amount > 0),
    CONSTRAINT chk_mission_rewards_item CHECK (
        (reward_type = 'item' AND item_content_node_id IS NOT NULL) OR
        (reward_type <> 'item' AND item_content_node_id IS NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- User game state and append-only attempt history.
CREATE TABLE user_game_states (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    current_region_content_node_id BIGINT UNSIGNED NULL,
    current_location_content_node_id BIGINT UNSIGNED NULL,
    total_xp BIGINT UNSIGNED NOT NULL DEFAULT 0,
    confianza INT UNSIGNED NOT NULL DEFAULT 0,
    valentia INT UNSIGNED NOT NULL DEFAULT 0,
    coins BIGINT UNSIGNED NOT NULL DEFAULT 0,
    current_streak_days INT UNSIGNED NOT NULL DEFAULT 0,
    longest_streak_days INT UNSIGNED NOT NULL DEFAULT 0,
    last_learning_date DATE NULL,
    state_version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    KEY idx_user_game_states_region (current_region_content_node_id),
    KEY idx_user_game_states_location (current_location_content_node_id),
    CONSTRAINT fk_user_game_states_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_game_states_region FOREIGN KEY (current_region_content_node_id) REFERENCES regions (content_node_id) ON DELETE SET NULL,
    CONSTRAINT fk_user_game_states_location FOREIGN KEY (current_location_content_node_id) REFERENCES locations (content_node_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE mission_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    mission_content_node_id BIGINT UNSIGNED NOT NULL,
    mission_version INT UNSIGNED NOT NULL,
    attempt_number INT UNSIGNED NOT NULL,
    status ENUM('started', 'in_progress', 'completed', 'failed', 'abandoned') NOT NULL DEFAULT 'started',
    score DECIMAL(6,2) NULL,
    earned_xp INT UNSIGNED NOT NULL DEFAULT 0,
    state_snapshot JSON NULL,
    started_at DATETIME(6) NOT NULL,
    completed_at DATETIME(6) NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_mission_attempts_number (user_id, mission_content_node_id, attempt_number),
    KEY idx_mission_attempts_user_status (user_id, status, updated_at),
    KEY idx_mission_attempts_mission_completed (mission_content_node_id, completed_at),
    CONSTRAINT fk_mission_attempts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_mission_attempts_mission FOREIGN KEY (mission_content_node_id) REFERENCES missions (content_node_id) ON DELETE RESTRICT,
    CONSTRAINT chk_mission_attempts_score CHECK (score IS NULL OR score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE mission_step_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mission_attempt_id BIGINT UNSIGNED NOT NULL,
    mission_step_id BIGINT UNSIGNED NOT NULL,
    attempt_number INT UNSIGNED NOT NULL,
    status ENUM('started', 'completed', 'failed', 'skipped') NOT NULL DEFAULT 'started',
    score DECIMAL(6,2) NULL,
    result JSON NULL,
    started_at DATETIME(6) NOT NULL,
    completed_at DATETIME(6) NULL,
    UNIQUE KEY uq_mission_step_attempts_number (mission_attempt_id, mission_step_id, attempt_number),
    KEY idx_mission_step_attempts_step (mission_step_id, status),
    CONSTRAINT fk_mission_step_attempts_attempt FOREIGN KEY (mission_attempt_id) REFERENCES mission_attempts (id) ON DELETE CASCADE,
    CONSTRAINT fk_mission_step_attempts_step FOREIGN KEY (mission_step_id) REFERENCES mission_steps (id) ON DELETE RESTRICT,
    CONSTRAINT chk_mission_step_attempts_score CHECK (score IS NULL OR score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE exercise_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    exercise_content_node_id BIGINT UNSIGNED NOT NULL,
    exercise_version INT UNSIGNED NOT NULL,
    mission_step_attempt_id BIGINT UNSIGNED NULL,
    attempt_number INT UNSIGNED NOT NULL,
    status ENUM('started', 'submitted', 'evaluated', 'failed') NOT NULL DEFAULT 'started',
    response JSON NULL,
    score DECIMAL(6,2) NULL,
    feedback JSON NULL,
    started_at DATETIME(6) NOT NULL,
    submitted_at DATETIME(6) NULL,
    evaluated_at DATETIME(6) NULL,
    UNIQUE KEY uq_exercise_attempts_number (user_id, exercise_content_node_id, attempt_number),
    KEY idx_exercise_attempts_user_recent (user_id, started_at),
    KEY idx_exercise_attempts_exercise_score (exercise_content_node_id, score),
    KEY idx_exercise_attempts_step (mission_step_attempt_id),
    CONSTRAINT fk_exercise_attempts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_exercise_attempts_exercise FOREIGN KEY (exercise_content_node_id) REFERENCES exercises (content_node_id) ON DELETE RESTRICT,
    CONSTRAINT fk_exercise_attempts_step FOREIGN KEY (mission_step_attempt_id) REFERENCES mission_step_attempts (id) ON DELETE SET NULL,
    CONSTRAINT chk_exercise_attempts_score CHECK (score IS NULL OR score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE user_mastery (
    user_id BIGINT UNSIGNED NOT NULL,
    content_node_id BIGINT UNSIGNED NOT NULL,
    mastery_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    ease_factor DECIMAL(5,2) NOT NULL DEFAULT 2.50,
    interval_days INT UNSIGNED NOT NULL DEFAULT 0,
    successful_reviews INT UNSIGNED NOT NULL DEFAULT 0,
    failed_reviews INT UNSIGNED NOT NULL DEFAULT 0,
    last_reviewed_at DATETIME(6) NULL,
    next_review_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    PRIMARY KEY (user_id, content_node_id),
    KEY idx_user_mastery_due (user_id, next_review_at),
    KEY idx_user_mastery_content (content_node_id, mastery_score),
    CONSTRAINT fk_user_mastery_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_mastery_content FOREIGN KEY (content_node_id) REFERENCES content_nodes (id) ON DELETE RESTRICT,
    CONSTRAINT chk_user_mastery_score CHECK (mastery_score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE user_inventory (
    user_id BIGINT UNSIGNED NOT NULL,
    item_content_node_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    first_acquired_at DATETIME(6) NOT NULL,
    last_acquired_at DATETIME(6) NOT NULL,
    metadata JSON NULL,
    PRIMARY KEY (user_id, item_content_node_id),
    KEY idx_user_inventory_item (item_content_node_id),
    CONSTRAINT fk_user_inventory_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_inventory_item FOREIGN KEY (item_content_node_id) REFERENCES item_definitions (content_node_id) ON DELETE RESTRICT,
    CONSTRAINT chk_user_inventory_quantity CHECK (quantity >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE game_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    currency ENUM('xp', 'confianza', 'valentia', 'coins') NOT NULL,
    amount_delta BIGINT NOT NULL,
    balance_after BIGINT UNSIGNED NOT NULL,
    reason_type VARCHAR(60) NOT NULL,
    reason_id BIGINT UNSIGNED NULL,
    idempotency_key VARCHAR(100) NOT NULL,
    metadata JSON NULL,
    created_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_game_ledger_idempotency (idempotency_key),
    KEY idx_game_ledger_user_currency (user_id, currency, created_at),
    KEY idx_game_ledger_reason (reason_type, reason_id),
    CONSTRAINT fk_game_ledger_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT chk_game_ledger_delta CHECK (amount_delta <> 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE user_npc_states (
    user_id BIGINT UNSIGNED NOT NULL,
    npc_content_node_id BIGINT UNSIGNED NOT NULL,
    trust_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    interaction_count INT UNSIGNED NOT NULL DEFAULT 0,
    memory_summary TEXT NULL,
    memory_facts JSON NULL,
    last_interaction_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    PRIMARY KEY (user_id, npc_content_node_id),
    KEY idx_user_npc_states_npc (npc_content_node_id, trust_score),
    CONSTRAINT fk_user_npc_states_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_npc_states_npc FOREIGN KEY (npc_content_node_id) REFERENCES npcs (content_node_id) ON DELETE RESTRICT,
    CONSTRAINT chk_user_npc_states_trust CHECK (trust_score <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Runtime conversations. The dialogue engine and evaluator are separately versioned.
CREATE TABLE conversation_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    scenario_content_node_id BIGINT UNSIGNED NOT NULL,
    scenario_version INT UNSIGNED NOT NULL,
    mission_step_attempt_id BIGINT UNSIGNED NULL,
    status ENUM('started', 'active', 'completed', 'failed', 'abandoned', 'moderated') NOT NULL DEFAULT 'started',
    dialogue_engine_version VARCHAR(80) NOT NULL,
    evaluator_version VARCHAR(80) NOT NULL,
    session_state JSON NULL,
    objective_result JSON NULL,
    final_score DECIMAL(6,2) NULL,
    started_at DATETIME(6) NOT NULL,
    completed_at DATETIME(6) NULL,
    updated_at DATETIME(6) NOT NULL,
    KEY idx_conversation_sessions_user_recent (user_id, started_at),
    KEY idx_conversation_sessions_scenario (scenario_content_node_id, status),
    KEY idx_conversation_sessions_step (mission_step_attempt_id),
    CONSTRAINT fk_conversation_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_conversation_sessions_scenario FOREIGN KEY (scenario_content_node_id) REFERENCES conversation_scenarios (content_node_id) ON DELETE RESTRICT,
    CONSTRAINT fk_conversation_sessions_step FOREIGN KEY (mission_step_attempt_id) REFERENCES mission_step_attempts (id) ON DELETE SET NULL,
    CONSTRAINT chk_conversation_sessions_score CHECK (final_score IS NULL OR final_score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE conversation_turns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_session_id BIGINT UNSIGNED NOT NULL,
    turn_number INT UNSIGNED NOT NULL,
    actor ENUM('learner', 'npc', 'system') NOT NULL,
    conversation_node_id BIGINT UNSIGNED NULL,
    text_content TEXT NULL,
    recognized_intents JSON NULL,
    engine_metadata JSON NULL,
    created_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_conversation_turns_number (conversation_session_id, turn_number),
    KEY idx_conversation_turns_node (conversation_node_id),
    CONSTRAINT fk_conversation_turns_session FOREIGN KEY (conversation_session_id) REFERENCES conversation_sessions (id) ON DELETE CASCADE,
    CONSTRAINT fk_conversation_turns_node FOREIGN KEY (conversation_node_id) REFERENCES conversation_nodes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE speech_recordings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    storage_disk VARCHAR(60) NOT NULL,
    object_key VARCHAR(700) NOT NULL,
    mime_type ENUM('audio/webm', 'video/webm') NOT NULL DEFAULT 'audio/webm',
    codec VARCHAR(40) NOT NULL DEFAULT 'opus',
    byte_size BIGINT UNSIGNED NOT NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    duration_ms INT UNSIGNED NULL,
    consent_version VARCHAR(40) NOT NULL,
    retention_until DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL,
    deleted_at DATETIME(6) NULL,
    UNIQUE KEY uq_speech_recordings_object (storage_disk, object_key),
    KEY idx_speech_recordings_user_created (user_id, created_at),
    KEY idx_speech_recordings_retention (retention_until, deleted_at),
    CONSTRAINT fk_speech_recordings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT chk_speech_recordings_size CHECK (byte_size > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE speaking_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    conversation_turn_id BIGINT UNSIGNED NULL,
    exercise_attempt_id BIGINT UNSIGNED NULL,
    speech_recording_id BIGINT UNSIGNED NULL,
    status ENUM('uploaded', 'transcribing', 'evaluating', 'evaluated', 'failed', 'deleted') NOT NULL DEFAULT 'uploaded',
    language_code VARCHAR(10) NOT NULL DEFAULT 'es-ES',
    target_text TEXT NULL,
    transcript TEXT NULL,
    transcript_confidence DECIMAL(5,4) NULL,
    pronunciation_score DECIMAL(5,2) NULL,
    fluency_score DECIMAL(5,2) NULL,
    intelligibility_score DECIMAL(5,2) NULL,
    task_completion_score DECIMAL(5,2) NULL,
    overall_score DECIMAL(5,2) NULL,
    feedback JSON NULL,
    transcription_provider VARCHAR(80) NULL,
    transcription_model VARCHAR(120) NULL,
    evaluator_provider VARCHAR(80) NULL,
    evaluator_model VARCHAR(120) NULL,
    evaluator_rubric_version VARCHAR(40) NULL,
    provider_trace_id VARCHAR(255) NULL,
    evaluation_metadata JSON NULL,
    created_at DATETIME(6) NOT NULL,
    evaluated_at DATETIME(6) NULL,
    updated_at DATETIME(6) NOT NULL,
    KEY idx_speaking_attempts_user_recent (user_id, created_at),
    KEY idx_speaking_attempts_status (status, created_at),
    KEY idx_speaking_attempts_turn (conversation_turn_id),
    KEY idx_speaking_attempts_exercise (exercise_attempt_id),
    KEY idx_speaking_attempts_recording (speech_recording_id),
    CONSTRAINT fk_speaking_attempts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_speaking_attempts_turn FOREIGN KEY (conversation_turn_id) REFERENCES conversation_turns (id) ON DELETE SET NULL,
    CONSTRAINT fk_speaking_attempts_exercise FOREIGN KEY (exercise_attempt_id) REFERENCES exercise_attempts (id) ON DELETE SET NULL,
    CONSTRAINT fk_speaking_attempts_recording FOREIGN KEY (speech_recording_id) REFERENCES speech_recordings (id) ON DELETE SET NULL,
    CONSTRAINT chk_speaking_attempts_parent CHECK (conversation_turn_id IS NOT NULL OR exercise_attempt_id IS NOT NULL),
    CONSTRAINT chk_speaking_attempts_confidence CHECK (transcript_confidence IS NULL OR transcript_confidence BETWEEN 0 AND 1),
    CONSTRAINT chk_speaking_attempts_pronunciation CHECK (pronunciation_score IS NULL OR pronunciation_score BETWEEN 0 AND 100),
    CONSTRAINT chk_speaking_attempts_fluency CHECK (fluency_score IS NULL OR fluency_score BETWEEN 0 AND 100),
    CONSTRAINT chk_speaking_attempts_intelligibility CHECK (intelligibility_score IS NULL OR intelligibility_score BETWEEN 0 AND 100),
    CONSTRAINT chk_speaking_attempts_task CHECK (task_completion_score IS NULL OR task_completion_score BETWEEN 0 AND 100),
    CONSTRAINT chk_speaking_attempts_overall CHECK (overall_score IS NULL OR overall_score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Commercial model. Never store card data; only opaque provider references.
CREATE TABLE subscription_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(160) NOT NULL,
    billing_interval ENUM('month', 'year') NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'EUR',
    amount_minor INT UNSIGNED NOT NULL,
    trial_days SMALLINT UNSIGNED NOT NULL DEFAULT 7,
    provider_price_ref VARCHAR(255) NULL,
    entitlements JSON NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    deleted_at DATETIME(6) NULL,
    UNIQUE KEY uq_subscription_plans_code (code),
    KEY idx_subscription_plans_active (active, deleted_at),
    CONSTRAINT chk_subscription_plans_amount CHECK (amount_minor > 0),
    CONSTRAINT chk_subscription_plans_trial CHECK (trial_days <= 90)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    subscription_plan_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_customer_ref VARCHAR(255) NULL,
    provider_subscription_ref VARCHAR(255) NULL,
    status ENUM('trialing', 'active', 'past_due', 'paused', 'cancelled', 'expired') NOT NULL,
    trial_starts_at DATETIME(6) NULL,
    trial_ends_at DATETIME(6) NULL,
    current_period_starts_at DATETIME(6) NULL,
    current_period_ends_at DATETIME(6) NULL,
    cancel_at_period_end BOOLEAN NOT NULL DEFAULT FALSE,
    cancelled_at DATETIME(6) NULL,
    ended_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_subscriptions_provider_ref (provider, provider_subscription_ref),
    KEY idx_subscriptions_user_status (user_id, status, current_period_ends_at),
    KEY idx_subscriptions_plan (subscription_plan_id, status),
    CONSTRAINT fk_subscriptions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_subscriptions_plan FOREIGN KEY (subscription_plan_id) REFERENCES subscription_plans (id) ON DELETE RESTRICT,
    CONSTRAINT chk_subscriptions_trial CHECK (
        trial_ends_at IS NULL OR trial_starts_at IS NULL OR trial_ends_at > trial_starts_at
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE subscription_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id BIGINT UNSIGNED NULL,
    provider VARCHAR(50) NOT NULL,
    provider_event_ref VARCHAR(255) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_payload JSON NOT NULL,
    occurred_at DATETIME(6) NOT NULL,
    received_at DATETIME(6) NOT NULL,
    processed_at DATETIME(6) NULL,
    processing_status ENUM('received', 'processed', 'ignored', 'failed') NOT NULL DEFAULT 'received',
    processing_error TEXT NULL,
    UNIQUE KEY uq_subscription_events_provider_ref (provider, provider_event_ref),
    KEY idx_subscription_events_processing (processing_status, received_at),
    KEY idx_subscription_events_subscription (subscription_id, occurred_at),
    CONSTRAINT fk_subscription_events_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Operational audit trail. Append-only by policy; polymorphic subjects are application-validated.
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    subject_type VARCHAR(80) NOT NULL,
    subject_id BIGINT UNSIGNED NULL,
    before_state JSON NULL,
    after_state JSON NULL,
    request_id CHAR(36) NULL,
    ip_hash CHAR(64) NULL,
    user_agent_family VARCHAR(120) NULL,
    created_at DATETIME(6) NOT NULL,
    KEY idx_audit_logs_subject (subject_type, subject_id, created_at),
    KEY idx_audit_logs_actor (actor_user_id, created_at),
    KEY idx_audit_logs_request (request_id),
    CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE domain_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aggregate_type VARCHAR(80) NOT NULL,
    aggregate_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(120) NOT NULL,
    payload JSON NOT NULL,
    occurred_at DATETIME(6) NOT NULL,
    published_at DATETIME(6) NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    UNIQUE KEY uq_domain_events_identity (aggregate_type, aggregate_id, event_type, occurred_at),
    KEY idx_domain_events_outbox (published_at, occurred_at),
    KEY idx_domain_events_aggregate (aggregate_type, aggregate_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
