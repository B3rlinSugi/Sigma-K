import { AuditActionType, AuditLogEntry } from '@/types/audit';
import { AuditLogDto } from '@/types/dto/audit.dto';

/**
 * Maps Backend action_event to Frontend AuditActionType
 */
export function mapActionEventToAuditAction(event: string): AuditActionType {
  if (event.includes('CREATE')) return 'CREATE';
  if (event.includes('UPDATE')) return 'UPDATE';
  if (event.includes('DELETE')) return 'DELETE';
  if (event.includes('SUBMIT')) return 'SUBMIT';
  if (event.includes('VERIF') || event.includes('REVIEW')) return 'VERIFY';
  if (event.includes('APPROVE') || event.includes('PROMOT')) return 'APPROVE';
  if (event.includes('REJECT') || event.includes('RETURN')) return 'REJECT';
  return 'UPDATE';
}

/**
 * Maps Backend AuditLogDto to Frontend AuditLogEntry
 */
export function mapAuditLogDtoToDomain(dto: AuditLogDto): AuditLogEntry {
  return {
    id: String(dto.id),
    timestamp: dto.created_at,
    actorId: dto.actor_id ? String(dto.actor_id) : 'SYSTEM',
    actorName: dto.actor_name || (dto.actor_id ? `User #${dto.actor_id}` : 'System Automated'),
    actorRole: dto.actor_role || 'SYSTEM',
    action: mapActionEventToAuditAction(dto.action_event),
    entity: dto.resource_entity,
    entityId: dto.resource_id ? String(dto.resource_id) : 'N/A',
    institutionId: dto.institution_id ? String(dto.institution_id) : undefined,
    institutionName: dto.institution_name,
    result: 'SUCCESS',
    ipAddress: dto.ip_address || '127.0.0.1',
    userAgent: dto.user_agent || undefined,
    oldValues: dto.old_payload,
    newValues: dto.new_payload,
    description: `Aksi ${dto.action_event} pada entitas ${dto.resource_entity} ID #${dto.resource_id}`,
  };
}

export function mapAuditLogsDtoToDomain(dtos: AuditLogDto[]): AuditLogEntry[] {
  return dtos.map(mapAuditLogDtoToDomain);
}
