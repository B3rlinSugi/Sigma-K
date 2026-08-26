export type AuditActionType = 'CREATE' | 'UPDATE' | 'DELETE' | 'SUBMIT' | 'VERIFY' | 'APPROVE' | 'REJECT';

export interface AuditLogEntry {
  id: string;
  timestamp: string;
  actorId: string;
  actorName: string;
  actorRole: string;
  action: AuditActionType;
  entity: string;
  entityId: string;
  institutionId?: string;
  institutionName?: string;
  result: 'SUCCESS' | 'FAILURE';
  ipAddress: string;
  userAgent?: string;
  oldValues?: any;
  newValues?: any;
  description: string;
}
