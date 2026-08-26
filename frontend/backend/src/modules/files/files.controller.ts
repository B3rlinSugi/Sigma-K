import {
  Controller,
  Get,
  Post,
  Delete,
  Param,
  UseInterceptors,
  UploadedFile,
  UseGuards,
  Res,
} from '@nestjs/common';
import { FileInterceptor } from '@nestjs/platform-express';
import { Response } from 'express';
import { FilesService } from './files.service';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';

@Controller('files')
export class FilesController {
  constructor(private readonly filesService: FilesService) {}

  @Post()
  @UseGuards(JwtAuthGuard)
  @UseInterceptors(FileInterceptor('file'))
  async uploadFile(@UploadedFile() file: Express.Multer.File) {
    return this.filesService.uploadFile(file);
  }

  @Get(':id')
  async getFile(@Param('id') id: string, @Res() res: Response) {
    const buffer = await this.filesService.getFile(id);
    res.setHeader('Content-Type', 'application/pdf');
    res.setHeader('Content-Disposition', `attachment; filename="${id}.pdf"`);
    res.send(buffer);
  }

  @Delete(':id')
  @UseGuards(JwtAuthGuard)
  async deleteFile(@Param('id') id: string) {
    const success = await this.filesService.deleteFile(id);
    return { success, message: 'Berkas berhasil dihapus.' };
  }
}
