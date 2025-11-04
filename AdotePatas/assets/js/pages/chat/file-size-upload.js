
// Configurações de upload
const UPLOAD_CONFIG = {
    documentos: {
        maxSize: 25 * 1024 * 1024, // 25MB
        minSize: 0 * 1024 * 1024, // 10MB
        tiposPermitidos: [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'application/rtf'
        ]
    },
    fotos: {
        maxSize: 10 * 1024 * 1024, // 10MB
        minSize: 0 * 1024 * 1024, // 5MB
        tiposPermitidos: [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp'
        ]
    },
    videos: {
        maxSize: 500 * 1024 * 1024, // 500MB
        minSize: 0 * 1024 * 1024, // 100MB
        tiposPermitidos: [
            'video/mp4',
            'video/avi',
            'video/mov',
            'video/wmv',
            'video/flv',
            'video/webm'
        ]
    }
};

class FileUploadHandler {
    constructor() {
        this.modal = new bootstrap.Modal(document.getElementById('fileModal'));
        this.initEvents();
    }

    initEvents() {
        document.querySelector('.chat-send-files').addEventListener('click', () => {
            this.modal.show();
        });

        document.getElementById('documentBtn').addEventListener('click', () => {
            document.getElementById('documentInput').click();
        });

        document.getElementById('mediaBtn').addEventListener('click', () => {
            document.getElementById('mediaInput').click();
        });

        document.getElementById('documentInput').addEventListener('change', (e) => {
            this.handleFileSelect(e, 'documentos');
        });

        document.getElementById('mediaInput').addEventListener('change', (e) => {
            this.handleFileSelect(e, 'media');
        });
    }

    async handleFileSelect(event, tipo) {
        const file = event.target.files[0];
        if (!file) return;

        this.modal.hide();

        try {
            await this.validarArquivo(file, tipo);
            await this.processarArquivo(file, tipo);
            this.showToast('Arquivo enviado com sucesso!', 'success');
        } catch (error) {
            this.showToast(error.message, 'danger');
        }

        event.target.value = '';
    }

    async validarArquivo(file, tipo) {
        if (tipo === 'media') {
            if (file.type.startsWith('image/')) {
                tipo = 'fotos';
            } else if (file.type.startsWith('video/')) {
                tipo = 'videos';
            } else {
                throw new Error('Tipo de arquivo não suportado');
            }
        }

        const config = UPLOAD_CONFIG[tipo];

        if (!config.tiposPermitidos.includes(file.type)) {
            throw new Error(`Tipo de arquivo não permitido para ${tipo}`);
        }

        if (file.size < config.minSize) {
            throw new Error(`Arquivo muito pequeno. Mínimo: ${this.formatBytes(config.minSize)}`);
        }

        if (file.size > config.maxSize) {
            throw new Error(`Arquivo muito grande. Máximo: ${this.formatBytes(config.maxSize)}`);
        }

        return true;
    }

    async processarArquivo(file, tipo) {
        this.showToast(`Processando ${file.name}...`, 'info');
        
        switch (tipo) {
            case 'fotos':
                await this.processarImagem(file);
                break;
            case 'videos':
                await this.processarVideo(file);
                break;
            case 'documentos':
                await this.processarDocumento(file);
                break;
        }

        await this.fazerUpload(file);
    }

    async processarImagem(file) {
        return new Promise(resolve => {
            setTimeout(() => {
                console.log('Imagem processada:', file.name);
                resolve();
            }, 2000);
        });
    }

    async processarVideo(file) {
        return new Promise(resolve => {
            setTimeout(() => {
                console.log('Vídeo processado:', file.name);
                resolve();
            }, 3000);
        });
    }

    async processarDocumento(file) {
        return new Promise(resolve => {
            setTimeout(() => {
                console.log('Documento validado:', file.name);
                resolve();
            }, 1000);
        });
    }

    async fazerUpload(file) {
        return new Promise((resolve) => {
            setTimeout(() => {
                console.log('Upload completo:', file.name);
                resolve();
            }, 2000);
        });
    }

    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    showToast(message, type = 'info') {
        const toast = document.getElementById('toast-notification');
        const toastIcon = document.getElementById('toast-icon');
        const toastMessage = document.getElementById('toast-message');
        
        // Remove todas as classes de tipo anteriores
        toast.className = 'adp-toast p-0';
        
        // Define o tipo e conteúdo
        toast.classList.add(`adp-toast--${type}`);
        toastMessage.textContent = message;
        
        // Define o ícone baseado no tipo
        let iconClass = '';
        switch(type) {
            case 'success':
                iconClass = 'fa-solid fa-check';
                break;
            case 'warning':
                iconClass = 'fa-solid fa-exclamation-triangle';
                break;
            case 'danger':
                iconClass = 'fa-solid fa-xmark';
                break;
            case 'info':
            default:
                iconClass = 'fa-solid fa-info';
                break;
        }
        
        toastIcon.className = 'adp-toast-icon';
        toastIcon.innerHTML = `<i class="${iconClass}" style="font-size: 1.3rem"></i>`;
        
        // Mostra o toast
        toast.style.display = 'flex';
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // Auto-esconde após 5 segundos
        setTimeout(() => {
            this.hideToast();
        }, 5000);
    }

    hideToast() {
        const toast = document.getElementById('toast-notification');
        toast.classList.remove('show');
        toast.classList.add('hide');
        
        setTimeout(() => {
            toast.style.display = 'none';
            toast.classList.remove('hide');
        }, 500);
    }
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    new FileUploadHandler();
});
