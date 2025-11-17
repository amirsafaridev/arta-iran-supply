const defaultConfig = {
    panel_title: "پنل مدیریت قراردادها",
    welcome_message: "به پنل مدیریت قراردادها خوش آمدید",
    new_contract_button: "➕ قرارداد جدید",
    background_color: "#f0f2f5",
    surface_color: "#ffffff",
    text_color: "#1a1a1a",
    primary_action_color: "#0066ff",
    secondary_action_color: "#dc3545"
};

let config = { ...defaultConfig };
let contracts = [];

// AJAX Helper Functions
function ajaxRequest(action, data, callback) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('nonce', artaPanel.nonce);
    
    for (let key in data) {
        if (data.hasOwnProperty(key)) {
            formData.append(key, data[key]);
        }
    }
    
    fetch(artaPanel.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(result => {
        if (callback) callback(result);
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        if (callback) callback({ success: false, data: { message: 'خطا در ارتباط با سرور' } });
    });
}

async function onConfigChange(newConfig) {
    config = { ...defaultConfig, ...newConfig };
    
    document.getElementById('panel-title').textContent = config.panel_title;
    document.getElementById('welcome-message').textContent = config.welcome_message;
    document.getElementById('new-contract-button-text').textContent = config.new_contract_button;
    
    document.body.style.background = config.background_color;
    
    const cards = document.querySelectorAll('.contract-card, .header, .empty-state, .modal-content, .loading');
    cards.forEach(card => {
        card.style.background = config.surface_color;
    });
    
    const textElements = document.querySelectorAll('.contract-title, .date-value, .form-input, .form-textarea, .modal-header h2, .form-label, .progress-label');
    textElements.forEach(el => {
        el.style.color = config.text_color;
    });
    
    const headerTitle = document.getElementById('panel-title');
    if (headerTitle) headerTitle.style.color = config.text_color;
    
    const primaryButtons = document.querySelectorAll('.btn-primary, .btn-submit');
    primaryButtons.forEach(btn => {
        btn.style.background = `linear-gradient(135deg, ${config.primary_action_color} 0%, ${adjustColor(config.primary_action_color, -15)} 100%)`;
    });
    
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.style.color = config.secondary_action_color;
        btn.style.borderColor = `${config.secondary_action_color}33`;
    });
}

function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '255, 255, 255';
}

function adjustColor(color, amount) {
    const num = parseInt(color.replace("#",""), 16);
    const r = Math.max(0, Math.min(255, (num >> 16) + amount));
    const g = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) + amount));
    const b = Math.max(0, Math.min(255, (num & 0x0000FF) + amount));
    return "#" + ((r << 16) | (g << 8) | b).toString(16).padStart(6, '0');
}

function renderContracts() {
    const container = document.getElementById('contracts-container');
    
    if (!container) return;
    
    container.innerHTML = '<div class="loading">در حال بارگذاری قراردادها...</div>';
    
    ajaxRequest('get_contracts', {}, function(response) {
        if (response.success && response.data.contracts) {
            contracts = response.data.contracts;
    
    if (contracts.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>هنوز قراردادی ثبت نشده</h3>
                <p>برای شروع، اولین قرارداد خود را ایجاد کنید</p>
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <div class="contracts-grid">
            ${contracts.map(contract => `
                        <div class="contract-card" data-id="${contract.id}">
                    <div class="contract-header">
                        <div>
                                    <div class="contract-title">${escapeHtml(contract.title)}</div>
                        </div>
                                <div class="contract-status status-${getStatusClass(contract.status)}">${getStatusLabel(contract.status)}</div>
                    </div>
                            <div class="contract-description">${escapeHtml(contract.description)}</div>
                    <div class="contract-dates">
                        <div class="date-box">
                            <div class="date-label">تاریخ شروع</div>
                            <div class="date-value">${formatDate(contract.start_date)}</div>
                        </div>
                        <div class="date-box">
                            <div class="date-label">تاریخ پایان</div>
                            <div class="date-value">${formatDate(contract.end_date)}</div>
                        </div>
                    </div>
                    <div class="progress-section">
                        <div class="progress-label">
                            <span>پیشرفت پروژه</span>
                            <span>${contract.progress}٪</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: ${contract.progress}%"></div>
                        </div>
                    </div>
                    <div class="contract-actions">
                                <button class="btn-action btn-view" id="view-btn-${contract.id}" onclick="viewContract(${contract.id}, this)">
                            <span class="btn-text">👁️ مشاهده جزئیات</span>
                            <span class="btn-spinner" style="display: none; align-items: center; justify-content: center; margin-right: 0.5rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite; display: inline-block;">
                                    <line x1="12" y1="2" x2="12" y2="6"></line>
                                    <line x1="12" y1="18" x2="12" y2="22"></line>
                                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                                    <line x1="2" y1="12" x2="6" y2="12"></line>
                                    <line x1="18" y1="12" x2="22" y2="12"></line>
                                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">❌</div>
                    <h3>خطا در بارگذاری قراردادها</h3>
                    <p>${response.data?.message || 'لطفاً دوباره تلاش کنید'}</p>
                </div>
            `;
        }
    });
}

function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        return date.toLocaleDateString('fa-IR');
    } catch (e) {
        return dateString;
    }
}

function getStatusClass(status) {
    const statusMap = {
        'in_progress': 'in-progress',
        'completed': 'completed',
        'cancelled': 'cancelled',
        'در حال انجام': 'in-progress',
        'انجام شده': 'completed',
        'لغو شده': 'cancelled'
    };
    
    return statusMap[status] || 'in-progress';
}

function getStatusLabel(status) {
    const statusMap = {
        'pending': 'در انتظار',
        'in_progress': 'در حال انجام',
        'completed': 'تکمیل شده',
        'cancelled': 'لغو شده'
    };
    
    return statusMap[status] || status;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function loadDashboardStats() {
    ajaxRequest('get_dashboard_stats', {}, function(response) {
        if (response.success && response.data) {
            const stats = response.data;
            
            // Update stats cards if they exist
            const statCards = document.querySelectorAll('.stat-card .stat-value');
            if (statCards.length >= 4) {
                statCards[0].textContent = stats.total || 0;
                statCards[1].textContent = stats.completed || 0;
                statCards[2].textContent = stats.in_progress || 0;
                statCards[3].textContent = formatNumber(stats.total_value || 0);
            }
        }
    });
}

function formatCurrency(value) {
    if (value >= 1000000000) {
        return (value / 1000000000).toFixed(2) + ' میلیارد';
    } else if (value >= 1000000) {
        return (value / 1000000).toFixed(2) + ' میلیون';
    } else if (value >= 1000) {
        return (value / 1000).toFixed(2) + ' هزار';
    }
    return value.toString();
}

function formatNumber(value) {
    if (value === null || value === undefined) return '0';
    return Number(value).toLocaleString('en-US');
}



function navigateTo(pageName) {
    // Remove active class from all menu items
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });

    // Add active class to clicked menu item
    const activeMenuItem = document.querySelector(`[data-page="${pageName}"]`);
    if (activeMenuItem) {
        activeMenuItem.classList.add('active');
    }

    // Hide all pages
    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });

    // Show selected page
    const selectedPage = document.getElementById(`${pageName}-page`);
    if (selectedPage) {
        selectedPage.classList.add('active');
    }
    
    // Load data for specific pages
    if (pageName === 'dashboard') {
        loadDashboardStats();
    } else if (pageName === 'contracts') {
        renderContracts();
    }

    // Scroll to top
    window.scrollTo(0, 0);
}

function viewContract(id, buttonElement) {
    const detailContent = document.getElementById('contract-detail-content');
    if (!detailContent) return;
    
    // Show loading spinner on button
    if (buttonElement) {
        const btnText = buttonElement.querySelector('.btn-text');
        const btnSpinner = buttonElement.querySelector('.btn-spinner');
        if (btnText) btnText.style.display = 'none';
        if (btnSpinner) btnSpinner.style.display = 'inline-flex';
        buttonElement.disabled = true;
    }
    
    detailContent.innerHTML = '<div class="loading">در حال بارگذاری جزئیات قرارداد...</div>';
    
    ajaxRequest('get_contract_detail', { contract_id: id }, function(response) {
        // Hide loading spinner on button
        if (buttonElement) {
            const btnText = buttonElement.querySelector('.btn-text');
            const btnSpinner = buttonElement.querySelector('.btn-spinner');
            if (btnText) btnText.style.display = 'inline';
            if (btnSpinner) btnSpinner.style.display = 'none';
            buttonElement.disabled = false;
        }
        
        if (response.success && response.data.contract) {
            const contract = response.data.contract;
            
            const stagesHTML = contract.stages ? contract.stages.map((stage, index) => {
                const icon = stage.status === 'completed' ? '✓' : 
                           stage.status === 'in_progress' ? '⟳' : '○';
                const filesHTML = stage.files && stage.files.length > 0 ? `
                    <div class="stage-files" style="margin-top: 1rem;">
                        <strong style="display: block; margin-bottom: 0.75rem; color: #1a1a1a;">فایل‌ها:</strong>
                        <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.5rem;">
                            ${stage.files.map((file) => {
                                const isImage = file.is_image || (file.type && file.type.startsWith('image/'));
                                
                                return `
                                    <div style="position: relative; width: 120px; text-align: center;">
                                        <div style="position: relative; width: 100px; height: 100px; margin: 0 auto; border-radius: 12px; overflow: hidden; background: #f0f2f5; border: 2px solid #e0e0e0; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" 
                                             onclick="window.open('${file.url}', '_blank')"
                                             onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                             onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                            ${isImage ? 
                                                `<img src="${file.url}" alt="${escapeHtml(file.name || 'فایل')}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\\'font-size: 3rem; color: #666;\\'>📄</div>';" />` :
                                                `<img src="${file.icon || '📄'}" alt="${escapeHtml(file.name || 'فایل')}" style="max-width: 60px; max-height: 60px; object-fit: contain;" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\\'font-size: 3rem; color: #666;\\'>📄</div>';" />`
                                            }
                                            <div style="position: absolute; bottom: 5px; right: 5px; background: rgba(0, 102, 255, 0.9); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" 
                                                 onclick="event.stopPropagation(); window.open('${file.url}', '_blank')"
                                                 onmouseover="this.style.background='rgba(0, 102, 255, 1)'; this.style.transform='scale(1.1)'"
                                                 onmouseout="this.style.background='rgba(0, 102, 255, 0.9)'; this.style.transform='scale(1)'">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                    <polyline points="7 10 12 15 17 10"></polyline>
                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                </svg>
                                            </div>
                                        </div>
                                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #666; word-break: break-word; line-height: 1.3; max-height: 2.6em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; padding: 0 5px;">
                                            ${escapeHtml(file.name || 'فایل')}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                ` : '';
                
                const statusLabel = getStatusLabel(stage.status);
                const statusClass = getStatusClass(stage.status);
                const statusColors = {
                    'pending': { bg: '#fff3cd', text: '#856404', border: '#ffc107' },
                    'in_progress': { bg: '#d1ecf1', text: '#0c5460', border: '#17a2b8' },
                    'completed': { bg: '#d4edda', text: '#155724', border: '#28a745' }
                };
                const statusColor = statusColors[stage.status] || statusColors['pending'];
                
                return `
                    <div class="timeline-item ${stage.status}">
                        <div class="timeline-marker ${stage.status}">${icon}</div>
                        <div class="timeline-content">
                            <div class="timeline-date">${formatDate(stage.date)}</div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                                <div class="timeline-title">${escapeHtml(stage.title)}</div>
                                <span style="display: inline-block; padding: 0.25rem 0.75rem; background: ${statusColor.bg}; color: ${statusColor.text}; border: 1px solid ${statusColor.border}; border-radius: 12px; font-size: 0.75rem; font-weight: 600; white-space: nowrap;">
                                    ${statusLabel}
                                </span>
                            </div>
                            <div class="timeline-description">${escapeHtml(stage.description)}</div>
                            ${filesHTML}
                        </div>
                    </div>
                `;
            }).join('') : '';

    detailContent.innerHTML = `
        <div class="page-header">
            <div class="breadcrumb">
                <span class="breadcrumb-item" onclick="navigateTo('dashboard')">داشبورد</span>
                <span class="breadcrumb-separator">←</span>
                <span class="breadcrumb-item" onclick="navigateTo('contracts')">لیست قراردادها</span>
                <span class="breadcrumb-separator">←</span>
                <span class="breadcrumb-item">جزئیات قرارداد</span>
            </div>
                    <h1 class="page-title">${escapeHtml(contract.title)}</h1>
                    <p class="page-subtitle">شماره قرارداد: ${escapeHtml(contract.contract_id)} | مشتری: ${escapeHtml(contract.client)}</p>
        </div>

        <div class="detail-info-grid">
            <div class="info-box">
                <div class="info-label">تاریخ شروع</div>
                <div class="info-value">${formatDate(contract.start_date)}</div>
            </div>
            <div class="info-box">
                <div class="info-label">تاریخ پایان</div>
                <div class="info-value">${formatDate(contract.end_date)}</div>
            </div>
            <div class="info-box">
                <div class="info-label">وضعیت</div>
                <div class="info-value">
                            <span class="contract-status status-${getStatusClass(contract.status)}">${getStatusLabel(contract.status)}</span>
                </div>
            </div>
            <div class="info-box">
                <div class="info-label">ارزش قرارداد</div>
                        <div class="info-value">${escapeHtml(contract.value)}</div>
            </div>
        </div>

        <div class="progress-section" style="margin-bottom: 2rem;">
            <div class="progress-label">
                <span>پیشرفت کلی پروژه</span>
                <span>${contract.progress}٪</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: ${contract.progress}%"></div>
            </div>
        </div>

        <div class="news-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span>📋</span>
                    <span>توضیحات قرارداد</span>
                </h2>
            </div>
            <div class="news-item">
                        <div class="news-description">${escapeHtml(contract.description)}</div>
            </div>
        </div>

                <div class="activity-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <span>⏱️</span>
                            <span>مراحل پروژه</span>
                        </h2>
                    </div>
                    <div class="timeline" id="stages-timeline">
                        ${stagesHTML || '<p>هنوز مرحله‌ای اضافه نشده است.</p>'}
                    </div>
                </div>
            `;
            
            // Store contract ID for stage operations
            window.currentContractId = contract.id;

    navigateTo('contract-detail');
        } else {
            detailContent.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">❌</div>
                    <h3>خطا در بارگذاری جزئیات</h3>
                    <p>${response.data?.message || 'لطفاً دوباره تلاش کنید'}</p>
                </div>
            `;
        }
    });
}



// Login functionality - Accept any username/password
function showLoginPage() {
    const loginPage = document.getElementById('login-page');
    const dashboardContainer = document.getElementById('dashboard-container');
    
    if (loginPage) loginPage.style.display = 'flex';
    if (dashboardContainer) dashboardContainer.style.display = 'none';
}

function showDashboard() {
    const loginPage = document.getElementById('login-page');
    const dashboardContainer = document.getElementById('dashboard-container');
    
    if (loginPage) loginPage.style.display = 'none';
    if (dashboardContainer) dashboardContainer.style.display = 'block';
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(el => {
        el.style.display = 'none';
        el.textContent = '';
    });
    
    const inputs = document.querySelectorAll('#login-form input');
    inputs.forEach(input => {
        input.classList.remove('error');
        input.closest('.input-container')?.classList.remove('error');
    });
}

function stripHtml(html) {
    // Create a temporary div element
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    // Return text content only
    return tmp.textContent || tmp.innerText || '';
}

function showError(fieldId, message) {
    const errorElement = document.getElementById(fieldId + '-error');
    if (errorElement) {
        // Strip HTML and show only plain text
        errorElement.textContent = stripHtml(message);
        errorElement.style.display = 'flex';
        
        // Add error class to input
        const input = document.getElementById(fieldId);
        if (input) {
            input.classList.add('error');
            input.closest('.input-container')?.classList.add('error');
        }
    }
}

function showGeneralError(message) {
    const errorElement = document.getElementById('general-error');
    if (errorElement) {
        // Strip HTML and show only plain text
        errorElement.textContent = stripHtml(message);
        errorElement.style.display = 'flex';
    }
}

function handleLogin(event) {
    event.preventDefault();
    
    // Clear previous errors
    clearErrors();
    
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const loginButton = document.querySelector('.btn-login');
    
    let hasError = false;
    
    // Validate inputs
    if (!username) {
        showError('username', 'لطفاً نام کاربری را وارد کنید.');
        hasError = true;
    }
    
    if (!password) {
        showError('password', 'لطفاً رمز عبور را وارد کنید.');
        hasError = true;
    }
    
    if (hasError) {
        return;
    }
    
    // Disable button during login
    if (loginButton) {
        loginButton.disabled = true;
        loginButton.innerHTML = '<span>در حال ورود...</span>';
    }
    
    ajaxRequest('user_login', {
        username: username,
        password: password,
        remember: true
    }, function(response) {
        if (response.success) {
            clearErrors();
            // Reload page to load user data properly
            window.location.reload();
        } else {
            const errorMessage = response.data?.message || 'خطا در ورود. لطفاً دوباره تلاش کنید.';
            
            // Try to show field-specific error, otherwise show general error
            if (errorMessage.includes('نام کاربری') || errorMessage.includes('username')) {
                showError('username', errorMessage);
            } else if (errorMessage.includes('رمز عبور') || errorMessage.includes('password')) {
                showError('password', errorMessage);
            } else {
                showGeneralError(errorMessage);
            }
            
            if (loginButton) {
                loginButton.disabled = false;
                loginButton.innerHTML = '<span>ورود</span><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>';
            }
        }
    });
}

function handleLogout() {
    if (artaPanel && artaPanel.logoutUrl) {
        window.location.href = artaPanel.logoutUrl;
    } else {
        showLoginPage();
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.reset();
        }
    }
}

function initLogin() {
    const loginForm = document.getElementById('login-form');
    const togglePassword = document.getElementById('toggle-password');
    const passwordInputEl = document.getElementById('password');
    
    // Handle form submission
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Clear errors on input focus
    const usernameInput = document.getElementById('username');
    
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                clearErrors();
            }
        });
    }
    
    if (passwordInputEl) {
        passwordInputEl.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                clearErrors();
            }
        });
    }
    
    // Toggle password visibility
    if (togglePassword && passwordInputEl) {
        togglePassword.addEventListener('click', () => {
            const type = passwordInputEl.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInputEl.setAttribute('type', type);
            
            // Update icon
            const eyeIcon = togglePassword.querySelector('.eye-icon');
            if (eyeIcon) {
                if (type === 'text') {
                    eyeIcon.innerHTML = `
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    `;
                } else {
                    eyeIcon.innerHTML = `
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    `;
                }
            }
        });
    }
}

async function initDashboard() {
    // Load dashboard stats
    loadDashboardStats();
    
    // Load contracts
    renderContracts();
    
    // Load recent activities
    loadRecentActivities();

    // Add event listeners to menu items
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', () => {
            const page = item.getAttribute('data-page');
            navigateTo(page);
        });
    });

    // Logout button
    const logoutButton = document.querySelector('.btn-logout');
    if (logoutButton) {
        logoutButton.addEventListener('click', () => {
            const logoutConfirm = document.createElement('div');
            logoutConfirm.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 2rem;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                z-index: 10000;
                text-align: center;
            `;
            logoutConfirm.innerHTML = `
                <h3 style="color: #1a1a1a; margin-bottom: 0.5rem;">خروج از حساب کاربری</h3>
                <p style="color: #666666; margin-bottom: 1.5rem;">آیا مطمئن هستید که می‌خواهید خارج شوید؟</p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button onclick="this.parentElement.parentElement.remove(); document.getElementById('logout-backdrop').remove();" 
                            style="padding: 0.8rem 2rem; background: #f0f2f5; border: none; border-radius: 10px; cursor: pointer; font-family: 'Vazirmatn', sans-serif; font-weight: 600;">
                        انصراف
                    </button>
                    <button onclick="handleLogout(); this.parentElement.parentElement.remove(); document.getElementById('logout-backdrop').remove();" 
                            style="padding: 0.8rem 2rem; background: #dc3545; color: white; border: none; border-radius: 10px; cursor: pointer; font-family: 'Vazirmatn', sans-serif; font-weight: 600;">
                        خروج
                    </button>
                </div>
            `;
            
            const backdrop = document.createElement('div');
            backdrop.id = 'logout-backdrop';
            backdrop.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9999;
            `;
            backdrop.onclick = () => {
                logoutConfirm.remove();
                backdrop.remove();
            };
            
            document.body.appendChild(backdrop);
            document.body.appendChild(logoutConfirm);
        });
    }

    if (window.elementSdk) {
        await window.elementSdk.init({
            defaultConfig,
            onConfigChange,
            mapToCapabilities: (config) => ({
                recolorables: [
                    {
                        get: () => config.background_color || defaultConfig.background_color,
                        set: (value) => {
                            config.background_color = value;
                            window.elementSdk.setConfig({ background_color: value });
                        }
                    },
                    {
                        get: () => config.surface_color || defaultConfig.surface_color,
                        set: (value) => {
                            config.surface_color = value;
                            window.elementSdk.setConfig({ surface_color: value });
                        }
                    },
                    {
                        get: () => config.text_color || defaultConfig.text_color,
                        set: (value) => {
                            config.text_color = value;
                            window.elementSdk.setConfig({ text_color: value });
                        }
                    },
                    {
                        get: () => config.primary_action_color || defaultConfig.primary_action_color,
                        set: (value) => {
                            config.primary_action_color = value;
                            window.elementSdk.setConfig({ primary_action_color: value });
                        }
                    },
                    {
                        get: () => config.secondary_action_color || defaultConfig.secondary_action_color,
                        set: (value) => {
                            config.secondary_action_color = value;
                            window.elementSdk.setConfig({ secondary_action_color: value });
                        }
                    }
                ],
                borderables: [],
                fontEditable: undefined,
                fontSizeable: undefined
            }),
            mapToEditPanelValues: (config) => new Map([
                ["panel_title", config.panel_title || defaultConfig.panel_title],
                ["welcome_message", config.welcome_message || defaultConfig.welcome_message],
                ["new_contract_button", config.new_contract_button || defaultConfig.new_contract_button]
            ])
        });
    }
}

// Initialize application
function init() {
    // Check if user is already logged in
    const loginPage = document.getElementById('login-page');
    const dashboardContainer = document.getElementById('dashboard-container');
    
    if (loginPage && loginPage.style.display !== 'none') {
        initLogin();
    }
    
    if (dashboardContainer && dashboardContainer.style.display !== 'none') {
        initDashboard();
    }
}

// Make functions available globally
window.handleLogout = handleLogout;
window.viewContract = viewContract;
window.showAddStageModal = showAddStageModal;
window.closeAddStageModal = closeAddStageModal;
window.addStage = addStage;
window.updateStageStatus = updateStageStatus;
window.deleteStage = deleteStage;
window.uploadStageFile = uploadStageFile;
window.deleteStageFile = deleteStageFile;

// Stage Management Functions
function showAddStageModal(contractId) {
    const modal = document.createElement('div');
    modal.id = 'add-stage-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    modal.innerHTML = `
        <div style="background: white; padding: 2rem; border-radius: 16px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <h2 style="margin-bottom: 1.5rem; color: #1a1a1a;">افزودن مرحله جدید</h2>
            <form id="add-stage-form" onsubmit="addStage(event, ${contractId})">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #666;">عنوان مرحله</label>
                    <input type="text" id="stage-title" required style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 8px; font-family: 'Vazirmatn', sans-serif;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #666;">تاریخ</label>
                    <input type="date" id="stage-date" required style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 8px; font-family: 'Vazirmatn', sans-serif;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #666;">وضعیت</label>
                    <select id="stage-status" style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 8px; font-family: 'Vazirmatn', sans-serif;">
                        <option value="pending">در انتظار</option>
                        <option value="in_progress">در حال انجام</option>
                        <option value="completed">تکمیل شده</option>
                    </select>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #666;">توضیحات</label>
                    <textarea id="stage-description" rows="4" style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 8px; font-family: 'Vazirmatn', sans-serif;"></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeAddStageModal()" style="padding: 0.8rem 1.5rem; background: #f0f2f5; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">انصراف</button>
                    <button type="submit" style="padding: 0.8rem 1.5rem; background: #0066ff; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">افزودن</button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.onclick = function(e) {
        if (e.target === modal) {
            closeAddStageModal();
        }
    };
}

function closeAddStageModal() {
    const modal = document.getElementById('add-stage-modal');
    if (modal) {
        modal.remove();
    }
}

function addStage(event, contractId) {
    event.preventDefault();
    
    const title = document.getElementById('stage-title').value;
    const date = document.getElementById('stage-date').value;
    const status = document.getElementById('stage-status').value;
    const description = document.getElementById('stage-description').value;
    
    ajaxRequest('add_contract_stage', {
        contract_id: contractId,
        title: title,
        date: date,
        status: status,
        description: description
    }, function(response) {
        if (response.success) {
            closeAddStageModal();
            // Reload contract detail
            viewContract(contractId);
            alert('مرحله با موفقیت اضافه شد.');
        } else {
            alert(response.data?.message || 'خطا در افزودن مرحله.');
        }
    });
}

function updateStageStatus(contractId, stageIndex, newStatus) {
    ajaxRequest('update_stage_status', {
        contract_id: contractId,
        stage_index: stageIndex,
        status: newStatus
    }, function(response) {
        if (response.success) {
            viewContract(contractId);
        } else {
            alert(response.data?.message || 'خطا در به‌روزرسانی وضعیت.');
        }
    });
}

function deleteStage(contractId, stageIndex) {
    if (!confirm('آیا مطمئن هستید که می‌خواهید این مرحله را حذف کنید؟')) {
        return;
    }
    
    ajaxRequest('delete_stage', {
        contract_id: contractId,
        stage_index: stageIndex
    }, function(response) {
        if (response.success) {
            viewContract(contractId);
            alert('مرحله حذف شد.');
        } else {
            alert(response.data?.message || 'خطا در حذف مرحله.');
        }
    });
}

function uploadStageFile(contractId, stageIndex, fileInput) {
    if (!fileInput.files || fileInput.files.length === 0) {
        return;
    }
    
    // Upload each file
    Array.from(fileInput.files).forEach((file, fileIndex) => {
        const formData = new FormData();
        formData.append('action', 'upload_stage_file');
        formData.append('nonce', artaPanel.nonce);
        formData.append('contract_id', contractId);
        formData.append('stage_index', stageIndex);
        formData.append('file', file);
        
        fetch(artaPanel.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                if (fileIndex === fileInput.files.length - 1) {
                    viewContract(contractId);
                    alert('فایل(ها) با موفقیت آپلود شد.');
                }
            } else {
                alert(result.data?.message || 'خطا در آپلود فایل.');
            }
        })
        .catch(error => {
            console.error('Upload Error:', error);
            alert('خطا در آپلود فایل.');
        });
    });
    
    // Reset file input
    fileInput.value = '';
}

function deleteStageFile(contractId, stageIndex, fileIndex) {
    if (!confirm('آیا مطمئن هستید که می‌خواهید این فایل را حذف کنید؟')) {
        return;
    }
    
    ajaxRequest('delete_stage_file', {
        contract_id: contractId,
        stage_index: stageIndex,
        file_index: fileIndex
    }, function(response) {
        if (response.success) {
            viewContract(contractId);
            alert('فایل حذف شد.');
        } else {
            alert(response.data?.message || 'خطا در حذف فایل.');
        }
    });
}

// Load recent activities
function loadRecentActivities() {
    const container = document.getElementById('recent-activities-list');
    if (!container) return;
    
    ajaxRequest('get_recent_activities', {}, function(response) {
        if (response.success && response.data.activities) {
            const activities = response.data.activities;
            
            if (activities.length === 0) {
                container.innerHTML = '<div class="activity-item"><div class="activity-content"><div class="activity-title" style="text-align: center; color: #999; padding: 2rem;">هیچ فعالیتی یافت نشد</div></div></div>';
                return;
            }
            
            let html = '';
            activities.forEach(function(activity) {
                const iconClass = activity.icon || 'info';
                const iconSymbol = activity.icon === 'success' ? '✓' : (activity.icon === 'warning' ? '⚠️' : '📝');
                
                html += '<div class="activity-item">';
                html += '<div class="activity-icon ' + iconClass + '">' + iconSymbol + '</div>';
                html += '<div class="activity-content">';
                html += '<div class="activity-title">' + escapeHtml(activity.title) + '</div>';
                html += '<div class="activity-time">' + escapeHtml(activity.time_ago) + '</div>';
                html += '</div>';
                html += '</div>';
            });
            
            container.innerHTML = html;
        } else {
            container.innerHTML = '<div class="activity-item"><div class="activity-content"><div class="activity-title" style="text-align: center; color: #999; padding: 2rem;">خطا در بارگذاری فعالیت‌ها</div></div></div>';
        }
    });
}

init();
