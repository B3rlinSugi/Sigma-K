/**
 * Submission Lifecycle & Snapshot DTOs matching CodeIgniter 4 / MySQL backend responses
 */

export interface SubmissionDto {
  id: number;
  institution_id: number;
  title: string;
  submission_year: number;
  current_state: string;
  author_id: number;
  description?: string | null;
  institution_name?: string;
  institution_code?: string;
  author_name?: string;
  current_version_id?: number | null;
  created_at: string;
  updated_at: string;
  units_count?: number;
  positions_count?: number;
}

export interface SubmissionVersionDto {
  id: number;
  submission_id: number;
  version_number: number;
  is_active: boolean;
  notes?: string | null;
  created_at: string;
}

export interface SubmissionUnitDto {
  id: number;
  version_id: number;
  original_unit_id?: number | null;
  parent_id?: number | null;
  unit_name: string;
  unit_code: string;
  unit_type?: string;
  change_type: 'NEW' | 'UPDATE' | 'DELETE' | 'UNCHANGED' | string;
  created_at: string;
}

export interface SubmissionPositionDto {
  id: number;
  version_unit_id: number;
  original_position_id?: number | null;
  position_name: string;
  position_type: string;
  formation_count: number;
  change_type: 'NEW' | 'UPDATE' | 'DELETE' | 'UNCHANGED' | string;
  created_at: string;
}
