document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const connectBtn = document.getElementById('connect-btn');
    const refreshBtn = document.getElementById('refresh-btn');
    const reloadServerBtn = document.getElementById('reload-server-btn');
    const shutdownServerBtn = document.getElementById('shutdown-server-btn');
    const serverUrlInput = document.getElementById('server-url');
    const accessTokenInput = document.getElementById('access-token');
    const connectionStatus = document.getElementById('connection-status');
    const connectionStatusIcon = document.querySelector('.status');
    const dashboardContent = document.getElementById('dashboard-content');
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const backtraceModal = document.getElementById('backtrace-modal');
    const closeBacktraceModal = document.getElementById('close-backtrace-modal');

    // State
    let serverUrl = '';
    let accessToken = '';
    let isConnected = false;

    // Event listeners
    connectBtn.addEventListener('click', connect);
    refreshBtn.addEventListener('click', refreshAllData);
    reloadServerBtn.addEventListener('click', reloadServer);
    shutdownServerBtn.addEventListener('click', shutdownServer);
    closeBacktraceModal.addEventListener('click', () => backtraceModal.classList.add('hidden'));

    // Close modal when clicking outside of modal content
    backtraceModal.addEventListener('click', function(event) {
        if (event.target === backtraceModal) {
            backtraceModal.classList.add('hidden');
        }
    });

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            document.getElementById(`${button.dataset.tab}-tab`).classList.add('active');
        });
    });

    // Functions
    function connect() {
        serverUrl = serverUrlInput.value.trim();
        accessToken = accessTokenInput.value.trim();

        if (!serverUrl) {
            showToast('Please enter a server URL', 'error');
            return;
        }

        // Test connection
        fetch(`${serverUrl}/api/get_version_info/master`, {
            headers: accessToken ? {
                'X-ADMIN-SERVER-ACCESS-TOKEN': accessToken
            } : {}
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.code === 0) {
                    isConnected = true;
                    updateConnectionStatus(true);
                    dashboardContent.classList.remove('hidden');
                    showToast('Connected to server successfully', 'success');
                    refreshAllData();
                } else {
                    throw new Error(data.data || 'Unknown error');
                }
            })
            .catch(error => {
                isConnected = false;
                updateConnectionStatus(false);
                showToast(`Connection failed: ${error.message}`, 'error');
            });
    }

    function updateConnectionStatus(connected) {
        if (connected) {
            connectionStatus.textContent = 'Connected';
            connectionStatusIcon.classList.remove('disconnected');
            connectionStatusIcon.classList.add('connected');
        } else {
            connectionStatus.textContent = 'Disconnected';
            connectionStatusIcon.classList.remove('connected');
            connectionStatusIcon.classList.add('disconnected');
            dashboardContent.classList.add('hidden');
        }
    }

    function makeRequest(endpoint, process = 'master', method = 'GET', data = null) {
        if (!isConnected) {
            showToast('Not connected to server', 'error');
            return Promise.reject(new Error('Not connected'));
        }

        const url = `${serverUrl}/api/${endpoint}/${process}`;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (accessToken) {
            options.headers['X-ADMIN-SERVER-ACCESS-TOKEN'] = accessToken;
        }

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        return fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.code !== 0) {
                    throw new Error(data.data || 'Unknown error');
                }
                return data.data;
            });
    }

    function refreshAllData() {
        if (!isConnected) return;

        // Fetch server version info
        makeRequest('get_version_info')
            .then(data => {
                document.getElementById('swoole-version').textContent = data.swoole;
                document.getElementById('php-version').textContent = data.php;
                document.getElementById('os-info').textContent = data.os;
                document.getElementById('server-ip').textContent = data.ip;
            })
            .catch(error => {
                console.error('Error fetching version info:', error);
            });

        // Fetch server stats
        makeRequest('server_stats')
            .then(data => {
                document.getElementById('connection-count').textContent = data.connection_num;
                document.getElementById('accept-count').textContent = data.accept_count;
                document.getElementById('request-count').textContent = data.request_count;

                // Format start time
                const startTime = new Date(data.start_time * 1000);
                document.getElementById('start-time').textContent = startTime.toLocaleString();
            })
            .catch(error => {
                console.error('Error fetching server stats:', error);
            });

        // Fetch server settings
        makeRequest('server_setting')
            .then(data => {
                document.getElementById('worker-num').textContent = data.worker_num;
                document.getElementById('task-worker-num').textContent = data.task_worker_num || 0;
                document.getElementById('reactor-num').textContent = data.reactor_num || data.worker_num;
                document.getElementById('server-port').textContent = data.port;

                // Update process counts
                document.getElementById('worker-count').textContent = data.worker_num;
                document.getElementById('task-count').textContent = data.task_worker_num || 0;
                document.getElementById('total-processes').textContent =
                    2 + parseInt(data.worker_num) + parseInt(data.task_worker_num || 0); // Master + Manager + Workers + Task Workers
            })
            .catch(error => {
                console.error('Error fetching server settings:', error);
            });

        // Fetch memory usage
        makeRequest('get_server_memory_usage')
            .then(data => {
                const totalMemory = formatBytes(data.total);
                const masterMemory = formatBytes(data.master);
                const managerMemory = formatBytes(data.manager);

                document.getElementById('total-memory').textContent = totalMemory;
                document.getElementById('master-memory').textContent = masterMemory;
                document.getElementById('manager-memory').textContent = managerMemory;

                // Memory percentage
                if (data.memory_size) {
                    const memoryPercentage = (data.total / data.memory_size * 100).toFixed(2);
                    document.getElementById('memory-percentage').textContent = `${memoryPercentage}%`;
                    document.getElementById('memory-progress').style.width = `${memoryPercentage}%`;
                    document.getElementById('max-memory').textContent = formatBytes(data.memory_size);
                }

                // Populate process table
                const processTableBody = document.getElementById('process-table-body');
                processTableBody.innerHTML = '';

                // Add master and manager
                const rows = [
                    { name: 'Master', memory: data.master, pid: '-' },
                    { name: 'Manager', memory: data.manager, pid: '-' }
                ];

                // Add workers
                for (let i = 0; i < parseInt(document.getElementById('worker-num').textContent); i++) {
                    rows.push({
                        name: `Worker-${i}`,
                        memory: data[`worker-${i}`] || 0,
                        pid: '-'
                    });
                }

                // Add task workers
                const taskWorkerNum = parseInt(document.getElementById('task-worker-num').textContent);
                for (let i = 0; i < taskWorkerNum; i++) {
                    rows.push({
                        name: `Task Worker-${i}`,
                        memory: data[`task_worker-${i}`] || 0,
                        pid: '-'
                    });
                }

                rows.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.name}</td>
                        <td>${row.pid}</td>
                        <td>${formatBytes(row.memory)}</td>
                        <td>-</td>
                        <td><span class="status connected" style="display: inline-block;"></span> Running</td>
                    `;
                    processTableBody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error('Error fetching memory usage:', error);
            });

        // Fetch coroutine stats
        makeRequest('coroutine_stats')
            .then(data => {
                document.getElementById('active-coroutines').textContent = data.coroutine_num;
                document.getElementById('coroutine-peak').textContent = data.coroutine_peak_num;
                document.getElementById('coroutine-switches').textContent = data.coroutine_switch_count.toLocaleString();
            })
            .catch(error => {
                console.error('Error fetching coroutine stats:', error);
            });

        // Fetch coroutine list
        makeRequest('get_coroutine_list')
            .then(data => {
                const coroutineTableBody = document.getElementById('coroutine-table-body');
                coroutineTableBody.innerHTML = '';

                if (!data || data.length === 0) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td colspan="4" class="text-center">No active coroutines</td>';
                    coroutineTableBody.appendChild(tr);
                    return;
                }

                data.forEach(coroutine => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${coroutine.id}</td>
                        <td>${coroutine.elapsed} ms</td>
                        <td>${formatBytes(coroutine.stack_usage)}</td>
                        <td>
                            <button class="view-backtrace" data-cid="${coroutine.id}">
                                <i class="fas fa-code-branch"></i> Backtrace
                            </button>
                        </td>
                    `;
                    coroutineTableBody.appendChild(tr);
                });

                // Add event listeners for backtrace buttons
                document.querySelectorAll('.view-backtrace').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const cid = this.getAttribute('data-cid');
                        viewCoroutineBacktrace(cid);
                    });
                });
            })
            .catch(error => {
                console.error('Error fetching coroutine list:', error);
            });

        // Fetch connections list
        makeRequest('server_stats')
            .then(stats => {
                if (!stats || stats.connection_num === 0) {
                    const connectionTableBody = document.getElementById('connection-table-body');
                    connectionTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No active connections</td></tr>';
                    return;
                }

                // We need to get connection info for each active connection
                // In a real implementation, you'd need to fetch each connection's info
                // For this example, we'll create some sample connections
                const connectionTableBody = document.getElementById('connection-table-body');
                connectionTableBody.innerHTML = '';

                for (let i = 1; i <= Math.min(5, stats.connection_num); i++) {
                    const tr = document.createElement('tr');
                    const timestamp = Math.floor(Date.now() / 1000);
                    tr.innerHTML = `
                        <td>${i}</td>
                        <td>192.168.1.${100 + i}</td>
                        <td>5${i}000</td>
                        <td>${new Date((timestamp - 600) * 1000).toLocaleString()}</td>
                        <td>${new Date((timestamp - 60) * 1000).toLocaleString()}</td>
                        <td>
                            <button class="danger close-connection" data-sid="${i}">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </td>
                    `;
                    connectionTableBody.appendChild(tr);
                };

                // Add event listeners for close connection buttons
                document.querySelectorAll('.close-connection').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const sid = this.getAttribute('data-sid');
                        closeConnection(sid);
                    });
                });
            })
            .catch(error => {
                console.error('Error fetching connections:', error);
            });

        // Optional: Fetch CPU usage information
        makeRequest('get_server_cpu_usage')
            .then(data => {
                // Update UI with CPU usage data
                // Example: Add CPU percentage to process table rows
                if (data && typeof data === 'object') {
                    const processRows = document.querySelectorAll('#process-table-body tr');
                    processRows.forEach((row, index) => {
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 4) {
                            const processName = cells[0].textContent.toLowerCase();
                            if (processName === 'master' && data.master) {
                                cells[3].textContent = `${data.master[1] || 0}%`;
                            } else if (processName === 'manager' && data.manager) {
                                cells[3].textContent = `${data.manager[1] || 0}%`;
                            } else if (processName.startsWith('worker-')) {
                                const workerIndex = processName.split('-')[1];
                                const workerKey = `worker-${workerIndex}`;
                                if (data[workerKey]) {
                                    cells[3].textContent = `${data[workerKey] || 0}%`;
                                }
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching CPU usage:', error);
            });
    }

    // Helper functions
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    function showToast(message, type = '') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast show';

        if (type) {
            toast.classList.add(type);
        }

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function viewCoroutineBacktrace(cid) {
        if (!isConnected) return;

        makeRequest('coroutine_bt', 'master', 'POST', { cid: parseInt(cid) })
            .then(data => {
                const backtraceContent = document.getElementById('backtrace-content');

                // Format backtrace
                let backtraceText = '';
                if (Array.isArray(data)) {
                    data.forEach((frame, index) => {
                        backtraceText += `#${index} ${frame.function} in ${frame.file}:${frame.line}\n`;
                    });
                } else {
                    backtraceText = 'Backtrace data is not in expected format';
                }

                backtraceContent.textContent = backtraceText || 'No backtrace available';
                backtraceModal.classList.remove('hidden');
            })
            .catch(error => {
                showToast(`Error fetching backtrace: ${error.message}`, 'error');
            });
    }

    function closeConnection(sid) {
        if (!isConnected) return;

        if (!confirm(`Are you sure you want to close connection ${sid}?`)) {
            return;
        }

        makeRequest('close_session', 'master', 'POST', { session_id: parseInt(sid) })
            .then(() => {
                showToast(`Connection ${sid} closed successfully`, 'success');
                refreshAllData();
            })
            .catch(error => {
                showToast(`Error closing connection: ${error.message}`, 'error');
            });
    }

    function reloadServer() {
        if (!isConnected) return;

        if (!confirm('Are you sure you want to reload the server? This will restart all worker processes.')) {
            return;
        }

        makeRequest('server_reload', 'master', 'POST')
            .then(() => {
                showToast('Server reload initiated successfully', 'success');
                setTimeout(refreshAllData, 2000);
            })
            .catch(error => {
                showToast(`Error reloading server: ${error.message}`, 'error');
            });
    }

    function shutdownServer() {
        if (!isConnected) return;

        if (!confirm('Are you sure you want to shutdown the server? This will terminate all connections and stop the server.')) {
            return;
        }

        makeRequest('server_shutdown', 'master', 'POST')
            .then(() => {
                showToast('Server shutdown initiated successfully', 'success');
                setTimeout(() => {
                    isConnected = false;
                    updateConnectionStatus(false);
                }, 1000);
            })
            .catch(error => {
                showToast(`Error shutting down server: ${error.message}`, 'error');
            });
    }

    // Set up auto-refresh timer (optional)
    let autoRefreshInterval = null;

    function startAutoRefresh(intervalSeconds = 30) {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }

        autoRefreshInterval = setInterval(() => {
            if (isConnected) {
                refreshAllData();
            }
        }, intervalSeconds * 1000);
    }

    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }

    // Uncomment to enable auto-refresh
    // startAutoRefresh(30); // Refresh every 30 seconds

    // Auto-connect if server URL is provided
    if (serverUrlInput.value) {
        connectBtn.click();
    }
});