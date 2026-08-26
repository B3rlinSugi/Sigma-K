import {
  IsNotEmpty,
  IsString,
  IsOptional,
  IsArray,
  ValidateNested,
  IsEnum,
} from 'class-validator';
import { Type } from 'class-transformer';
import { WorkflowStatus } from '../../../config/workflow.config';

export class CreateSubmissionItemDto {
  @IsEnum(['ORGANIZATION_UNIT', 'TUPOKSI', 'INSTITUTION_PROFILE'])
  targetEntityType: 'ORGANIZATION_UNIT' | 'TUPOKSI' | 'INSTITUTION_PROFILE';

  @IsString()
  @IsOptional()
  targetEntityId?: string;

  @IsEnum(['CREATE', 'UPDATE', 'DELETE'])
  actionType: 'CREATE' | 'UPDATE' | 'DELETE';

  @IsString()
  @IsOptional()
  fieldName?: string;

  @IsString()
  @IsNotEmpty({ message: 'Label butir perubahan wajib diisi.' })
  label: string;

  @IsOptional()
  payloadBefore?: Record<string, any>;

  @IsOptional()
  payloadAfter?: Record<string, any>;
}

export class CreateSubmissionTicketDto {
  @IsString()
  @IsNotEmpty({ message: 'ID Instansi wajib diisi.' })
  institutionId: string;

  @IsString()
  @IsNotEmpty({ message: 'Jenis pengajuan wajib diisi.' })
  submissionType: string;

  @IsString()
  @IsNotEmpty({ message: 'Judul pengajuan wajib diisi.' })
  title: string;

  @IsString()
  @IsOptional()
  submissionNotes?: string;

  @IsString()
  @IsOptional()
  legalDocPath?: string;

  @IsString()
  @IsOptional()
  legalDocName?: string;

  @IsArray()
  @ValidateNested({ each: true })
  @Type(() => CreateSubmissionItemDto)
  items: CreateSubmissionItemDto[];
}

export class TransitionWorkflowDto {
  @IsString()
  @IsNotEmpty({ message: 'Status target wajib diisi.' })
  targetState: WorkflowStatus;

  @IsString()
  @IsOptional()
  notes?: string;
}

export class ResubmitRevisionDto {
  @IsString()
  @IsNotEmpty({ message: 'Tanggapan perbaikan operator wajib diisi.' })
  operatorResponseNote: string;

  @IsArray()
  @IsOptional()
  revisedItems?: Array<{
    submissionItemId?: string;
    payloadAfter?: Record<string, any>;
  }>;
}
