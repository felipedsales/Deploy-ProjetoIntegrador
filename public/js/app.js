// Configuração da API
const API_BASE = '/.netlify/functions/api';

// Funções de UI
function showLoginModal() {
    const modal = new bootstrap.Modal(document.getElementById('loginModal'));
    modal.show();
}

function showRegisterModal() {
    const modal = new bootstrap.Modal(document.getElementById('registerModal'));
    modal.show();
}

function showVagas() {
    document.getElementById('vagas').scrollIntoView({ behavior: 'smooth' });
    loadVagas();
}

function showEmpresas() {
    document.getElementById('empresas').scrollIntoView({ behavior: 'smooth' });
    loadEmpresas();
}

// Funções da API
async function apiCall(endpoint, options = {}) {
    try {
        const response = await fetch(`${API_BASE}${endpoint}`, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// Carregar vagas
async function loadVagas() {
    try {
        const vagas = await apiCall('/vagas');
        const container = document.getElementById('vagas-container');
        
        if (vagas.length === 0) {
            container.innerHTML = '<div class="col-12 text-center"><p>Nenhuma vaga disponível no momento.</p></div>';
            return;
        }

        container.innerHTML = vagas.map(vaga => `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">${vaga.titulo}</h5>
                        <h6 class="card-subtitle mb-2 text-muted">${vaga.empresa}</h6>
                        <p class="card-text">${vaga.descricao.substring(0, 100)}...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary">${vaga.tipo_contrato}</span>
                            <span class="text-muted">R$ ${vaga.salario}</span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-outline-primary btn-sm" onclick="viewVaga(${vaga.id})">
                            Ver Detalhes
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Erro ao carregar vagas:', error);
        document.getElementById('vagas-container').innerHTML = 
            '<div class="col-12 text-center"><p>Erro ao carregar vagas. Tente novamente.</p></div>';
    }
}

// Carregar empresas
async function loadEmpresas() {
    try {
        const empresas = await apiCall('/empresas');
        const container = document.getElementById('empresas-container');
        
        if (empresas.length === 0) {
            container.innerHTML = '<div class="col-12 text-center"><p>Nenhuma empresa cadastrada.</p></div>';
            return;
        }

        container.innerHTML = empresas.map(empresa => `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <img src="${empresa.logo || 'img/empregado.png'}" alt="${empresa.nome}" 
                             class="rounded-circle mb-3" width="80" height="80">
                        <h5 class="card-title">${empresa.nome}</h5>
                        <p class="card-text">${empresa.descricao}</p>
                        <p class="text-muted">${empresa.setor}</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-outline-primary btn-sm" onclick="viewEmpresa(${empresa.id})">
                            Ver Empresa
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Erro ao carregar empresas:', error);
        document.getElementById('empresas-container').innerHTML = 
            '<div class="col-12 text-center"><p>Erro ao carregar empresas. Tente novamente.</p></div>';
    }
}

// Login
async function handleLogin(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        email: formData.get('email'),
        password: formData.get('password')
    };

    try {
        const response = await apiCall('/auth/login', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (response.success) {
            localStorage.setItem('user', JSON.stringify(response.user));
            bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
            updateUI();
            showAlert('Login realizado com sucesso!', 'success');
        } else {
            showAlert(response.message || 'Erro no login', 'danger');
        }
    } catch (error) {
        showAlert('Erro ao fazer login. Tente novamente.', 'danger');
    }
}

// Registro
async function handleRegister(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        password: formData.get('password')
    };

    try {
        const response = await apiCall('/auth/register', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (response.success) {
            bootstrap.Modal.getInstance(document.getElementById('registerModal')).hide();
            showAlert('Cadastro realizado com sucesso! Faça login para continuar.', 'success');
        } else {
            showAlert(response.message || 'Erro no cadastro', 'danger');
        }
    } catch (error) {
        showAlert('Erro ao fazer cadastro. Tente novamente.', 'danger');
    }
}

// Logout
async function handleLogout() {
    try {
        await apiCall('/auth/logout', { method: 'POST' });
        localStorage.removeItem('user');
        updateUI();
        showAlert('Logout realizado com sucesso!', 'success');
    } catch (error) {
        console.error('Erro no logout:', error);
    }
}

// Atualizar UI baseado no estado do usuário
function updateUI() {
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    const navbar = document.querySelector('.navbar-nav');
    
    if (user) {
        navbar.innerHTML = `
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user"></i> ${user.name}
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="viewProfile()">Meu Perfil</a></li>
                    <li><a class="dropdown-item" href="#" onclick="viewApplications()">Minhas Candidaturas</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="handleLogout()">Sair</a></li>
                </ul>
            </li>
        `;
    } else {
        navbar.innerHTML = `
            <a class="nav-link" href="#" onclick="showLoginModal()">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </a>
            <a class="nav-link" href="#" onclick="showRegisterModal()">
                <i class="fas fa-user-plus"></i> Cadastrar
            </a>
        `;
    }
}

// Funções auxiliares
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function viewVaga(id) {
    // Implementar visualização detalhada da vaga
    showAlert('Funcionalidade em desenvolvimento', 'info');
}

function viewEmpresa(id) {
    // Implementar visualização detalhada da empresa
    showAlert('Funcionalidade em desenvolvimento', 'info');
}

function viewProfile() {
    // Implementar visualização do perfil
    showAlert('Funcionalidade em desenvolvimento', 'info');
}

function viewApplications() {
    // Implementar visualização das candidaturas
    showAlert('Funcionalidade em desenvolvimento', 'info');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Carregar dados iniciais
    loadVagas();
    loadEmpresas();
    updateUI();
    
    // Event listeners para formulários
    document.getElementById('loginForm').addEventListener('submit', handleLogin);
    document.getElementById('registerForm').addEventListener('submit', handleRegister);
    
    // Smooth scrolling para links do navbar
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}); 