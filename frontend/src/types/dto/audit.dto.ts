/**
 * Audit Log DTO matching CodeIgniter 4 / MySQL backend responses
 */

export interface AuditLogDto {
  id: number;
  actor_id: number | null;
  actor_role: string | null;
  action_event: string;
  resource_entity: string;
  resource_id: number | null;
  institution_id: number | null;
  old_payload: Record<string, unknown> | null;
  new_payload: Record<string, unknown> | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
  actor_name?: string;
  institution_name?: string;
}
