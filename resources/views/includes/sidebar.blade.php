@php
    $sidebarUser = Auth::user();
    $sidebarRoleLabels = [
        1 => 'Admin',
        2 => 'Clerk',
        3 => 'Supervisor',
        4 => 'Manager',
        5 => 'Super User',
        6 => 'ZINARA Supervisor',
        7 => 'ZINARA Clerk',
    ];
    $sidebarRole = $sidebarRoleLabels[$sidebarUser->role_id] ?? 'Workspace User';
    
    // Check if user is ZINARA specific roles
    $isZINARASupervisor = ($sidebarUser->role_id == 6);
    $isZINARAClerk = ($sidebarUser->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
@endphp

<aside id="sidebar" class="sidebar">
    <div class="sidebar-brand-panel">
        <h2>Operations Flow</h2>
        <p>Navigate collections, face values, reporting, and controls from one place.</p>
        <div class="sidebar-stack">
            <span class="sidebar-badge"><i class="bi bi-person-badge"></i> {{ $sidebarRole }}</span>
            @if($sidebarUser->site)
                <span class="sidebar-badge"><i class="bi bi-geo-alt"></i> {{ $sidebarUser->site->site_name }}</span>
            @endif
            @if($sidebarUser->network)
                <span class="sidebar-badge"><i class="bi bi-diagram-3"></i> {{ $sidebarUser->network->name }}</span>
            @endif
        </div>
    </div>

    <ul class="sidebar-nav" id="sidebar-nav">

        <li class="nav-item">
            <a class="nav-link collapsed" href="{{ route('home') }}">
                <i class="bi bi-grid"></i>
                <span>Dashboard</span>
            </a>
        </li>
       
       @if($isZINARASupervisor) {{-- ZINARA Supervisor --}}
    
            {{-- ZINARA Module --}}
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>ZINARA</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('supervisorfacevalues.index') }}">
                            <i class="bi bi-gear" style="font-size: 1.5em;"></i><span>Manage FaceValues</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.hub') }}">
                            <i class="bi bi-grid-1x2" style="font-size: 1.5em;"></i><span>Reports Hub</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.stock') }}">
                            <i class="bi bi-box-seam" style="font-size: 1.5em;"></i><span>Stock Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.trace') }}">
                            <i class="bi bi-search" style="font-size: 1.5em;"></i><span>Trace FaceValue</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('clientfvreport') }}">
                            <i class="bi bi-calendar-day" style="font-size: 1.5em;"></i><span>Daily Entries</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('courier.sales.index') }}">
                            <i class="bi bi-receipt" style="font-size: 1.5em;"></i><span>Courier Sales</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cumulativefvreport') }}">
                            <i class="bi bi-calendar3" style="font-size: 1.5em;"></i><span>Cumulative Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.exceptions') }}">
                            <i class="bi bi-clipboard2-pulse" style="font-size: 1.5em;"></i><span>Exceptions</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Users Module - Create ZINARA Clerks --}}
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#users-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-people"></i><span>Users</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="users-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('teams.create') }}">
                            <i class="bi bi-person-plus" style="font-size: 1.5em;"></i><span>Create ZINARA Clerk</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('teams.index') }}">
                            <i class="bi bi-person-lines-fill" style="font-size: 1.5em;"></i><span>Manage ZINARA Clerks</span>
                        </a>
                    </li>
                </ul>
            </li>

           

            {{-- Sites Module --}}
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#sites-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-building"></i><span>Sites</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="sites-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('sites') }}">
                            <i class="bi bi-eye" style="font-size: 1.5em;"></i><span>View Sites</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('getsite') }}">
                            <i class="bi bi-plus-circle" style="font-size: 1.5em;"></i><span>Create Site</span>
                        </a>
                    </li>
                </ul>
            </li>

        @elseif($isZINARAClerk) {{-- ZINARA Clerk --}}

            {{-- ZINARA Module only for clerks --}}
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>ZINARA</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('facevaluelist') }}">
                            <i class="bi bi-file-earmark-text" style="font-size: 1.5em;"></i><span>Face.V Declaration</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('gethistory') }}">
                            <i class="bi bi-clock-history" style="font-size: 1.5em;"></i><span>History</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('courier.sales.index') }}">
                            <i class="bi bi-receipt" style="font-size: 1.5em;"></i><span>Courier Sales</span>
                        </a>
                    </li>
                </ul>
            </li>

        @elseif(Auth::user()->role_id == 5) {{-- Super User --}}
             
           <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#tables-navcccc" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-layout-money"></i><span>Cash In Hand</span><i class="bi-chevron-down ms-auto"></i> 
                </a>
                <ul id="tables-navcccc" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('cash-in-hand-balances.index') }}">
                            <i class="bi bi-building" style="font-size: 1.5em;"></i><span>Balances</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>ZINARA</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('supervisorfacevalues.index') }}">
                            <i class="bi bi-gear" style="font-size: 1.5em;"></i><span>Manage FaceValues</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.hub') }}">
                            <i class="bi bi-grid-1x2" style="font-size: 1.5em;"></i><span>Reports Hub</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.stock') }}">
                            <i class="bi bi-box-seam" style="font-size: 1.5em;"></i><span>Stock Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.trace') }}">
                            <i class="bi bi-search" style="font-size: 1.5em;"></i><span>Trace FaceValue</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('clientfvreport') }}">
                            <i class="bi bi-calendar-day" style="font-size: 1.5em;"></i><span>Daily Entries</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('courier.sales.index') }}">
                            <i class="bi bi-receipt" style="font-size: 1.5em;"></i><span>Courier Sales</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cumulativefvreport') }}">
                            <i class="bi bi-calendar3" style="font-size: 1.5em;"></i><span>Cumulative Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.exceptions') }}">
                            <i class="bi bi-clipboard2-pulse" style="font-size: 1.5em;"></i><span>Exceptions</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#collection-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-newspaper"></i><span>Daily Collections</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="collection-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('getmanagesheet') }}">
                            <i class="bi bi-cash-stack" style="font-size: 1.5em;"></i><span>Manage Collections</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('gettransactions') }}">
                            <i class="bi bi-piggy-bank" style="font-size: 1.5em;"></i><span>Manage Transactions</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#tables-navcccc" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-layout-text-window-reverse"></i><span>Daily Collection Reports</span><i class="bi-chevron-down ms-auto"></i> 
                </a>
                <ul id="tables-navcccc" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('bysitesreports') }}">
                            <i class="bi bi-building" style="font-size: 1.5em;"></i><span>Site Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('collectionreports') }}">
                            <i class="bi bi-globe" style="font-size: 1.5em;"></i><span>Network Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cumulativeNetworkReport') }}">
                            <i class="bi bi-star" style="font-size: 1.5em;"></i><span>Cumulative Reports</span>
                        </a>
                    </li>
                     <li>
                        <a href="{{route('user.reports')}}">
                            <i class="bi bi-person" style="font-size: 1.5em;"></i><span>Individual Reports</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#charts-navzam" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-pen"></i><span>Collections Ammendments</span><i class="bi-chevron-down ms-auto"></i>
                </a>
                <ul id="charts-navzam" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('dailycollection.ammendments') }}">
                            <i class="bi bi-eye" style="font-size: 1.5em;"></i><span>View Collections Transactions</span>
                        </a>
                    </li>
                   <li>
                        <a href="{{ route('dailycollection.ammendmentrequestlist') }}">
                            <i class="bi bi-plus-circle" style="font-size: 1.5em;"></i><span>View Transactions</span>
                        </a>
                    </li> 
                </ul>
            </li>
             <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#charts-navzamfv" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-files"></i><span>FV Ammendments</span><i class="bi-chevron-down ms-auto"></i>
                </a>
                <ul id="charts-navzamfv" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('facevalues.allusers') }}">
                            <i class="bi bi-eye" style="font-size: 1.5em;"></i><span>View Users</span>
                        </a>
                    </li>
                </ul>
            </li> 
            
           <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-people"></i><span>Users</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="charts-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('teams.create') }}">
                            <i class="bi bi-person-plus" style="font-size: 1.5em;"></i><span>Create User</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('teams.index') }}">
                            <i class="bi bi-person-lines-fill" style="font-size: 1.5em;"></i><span>Manage Users</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#charts-navz" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-share"></i><span>Networks</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="charts-navz" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('networks.index') }}">
                            <i class="bi bi-eye" style="font-size: 1.5em;"></i><span>View Networks</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('networks.create') }}">
                            <i class="bi bi-plus-circle" style="font-size: 1.5em;"></i><span>Create Network</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#charts-navzc" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-map"></i><span>Sites</span><i class="bi-chevron-down ms-auto"></i>
                </a>
                <ul id="charts-navzc" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('sites') }}">
                            <i class="bi bi-eye" style="font-size: 1.5em;"></i><span>View Sites</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('getsite') }}">
                            <i class="bi bi-plus-circle" style="font-size: 1.5em;"></i><span>Create Site</span>
                        </a>
                    </li>
                </ul>
            </li>
            
             <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#database-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-database"></i><span>Budgets</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="database-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('budgets.index') }}">
                            <i class="bi bi-bank" style="font-size: 1.5em;"></i><span>Target Review</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('budgets.create') }}">
                            <i class="bi bi-table" style="font-size: 1.5em;"></i><span>Enter Site Budgets</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('budgets.charts.usd') }}">
                            <i class="bi bi-cash-stack" style="font-size: 1.5em;"></i><span>USD Chart</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('budgets.charts.zwg') }}">
                            <i class="bi bi-wallet2" style="font-size: 1.5em;"></i><span>ZWG Chart</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#collection-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-newspaper"></i><span>Data Analytics</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="collection-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('ai.dashboard') }}">
                            <i class="bi bi-bar-chart-steps"></i>
                            <span>AI Analytics</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('reports.hub') }}">
                            <i class="bi bi-bar-chart-steps"></i>
                            <span>Reports Hub</span>
                        </a>
                    </li>
                </ul>
            </li>
          
        @elseif(Auth::user()->role_id == 2) {{-- Regular Clerk --}}

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>ZINARA</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('facevaluelist') }}">
                            <i class="bi bi-file-earmark-text" style="font-size: 1.5em;"></i><span>Face.V Declaration</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('gethistory') }}">
                            <i class="bi bi-clock-history" style="font-size: 1.5em;"></i><span>History</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-gem"></i><span>Collection Sheet</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="icons-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('collection') }}">
                            <i class="bi bi-plus-square" style="font-size: 1.5em;"></i><span>Create</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('getmanagesheet') }}">
                            <i class="bi bi-cash-coin" style="font-size: 1.5em;"></i><span>Manage Collections</span>
                        </a>
                    </li>
                </ul>
            </li>

         
    

            @elseif(Auth::user()->role_id == 3) {{-- Regular Supervisor --}}

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>ZINARA</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('supervisorfacevalues.index') }}">
                            <i class="bi bi-gear" style="font-size: 1.5em;"></i><span>Manage FaceValues</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.hub') }}">
                            <i class="bi bi-grid-1x2" style="font-size: 1.5em;"></i><span>Reports Hub</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.stock') }}">
                            <i class="bi bi-box-seam" style="font-size: 1.5em;"></i><span>Stock Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.trace') }}">
                            <i class="bi bi-search" style="font-size: 1.5em;"></i><span>Trace FaceValue</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('clientfvreport') }}">
                            <i class="bi bi-calendar-day" style="font-size: 1.5em;"></i><span>Daily Entries</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cumulativefvreport') }}">
                            <i class="bi bi-calendar3" style="font-size: 1.5em;"></i><span>Cumulative Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevalues.reports.exceptions') }}">
                            <i class="bi bi-clipboard2-pulse" style="font-size: 1.5em;"></i><span>Exceptions</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#tables-navcccc" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-layout-text-window-reverse"></i><span>Daily Collection Reports</span><i class="bi-chevron-down ms-auto"></i> 
                </a>
                <ul id="tables-navcccc" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('bysitesreports') }}">
                            <i class="bi bi-building" style="font-size: 1.5em;"></i><span>Site Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('collectionreports') }}">
                            <i class="bi bi-globe" style="font-size: 1.5em;"></i><span>Network Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cumulativeNetworkReport') }}">
                            <i class="bi bi-star" style="font-size: 1.5em;"></i><span>Cumulative Reports</span>
                        </a>
                    </li>
                     <li>
                        <a href="{{route('user.reports')}}">
                            <i class="bi bi-person" style="font-size: 1.5em;"></i><span>Individual Reports</span>
                        </a>
                    </li>
                </ul>
            </li>
            
           

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#collection-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-newspaper"></i><span>Data Analytics</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="collection-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('ai.dashboard') }}">
                            <i class="bi bi-bar-chart-steps"></i>
                            <span>AI Analytics</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('reports.hub') }}">
                            <i class="bi bi-bar-chart-steps"></i>
                            <span>Reports Hub</span>
                        </a>
                    </li>
                </ul>
            </li>

            

            @else

            {{-- Default for other roles --}}
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>ZINARA</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('gethistory') }}">
                            <i class="bi bi-clock-history" style="font-size: 1.5em;"></i><span>History</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('facevaluelist') }}">
                            <i class="bi bi-file-earmark-text" style="font-size: 1.5em;"></i><span>Face.V Declaration</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-gem"></i><span>Collection Sheet</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="icons-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('collection') }}">
                            <i class="bi bi-plus-square" style="font-size: 1.5em;"></i><span>Create</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('getmanagesheet') }}">
                            <i class="bi bi-pencil-square" style="font-size: 1.5em;"></i><span>Manage</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-layout-text-window-reverse"></i><span>Full Cover</span><i class="bi-chevron-down ms-auto"></i>
                </a>
                <ul id="tables-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('getfullcover') }}">
                            <i class="bi bi-plus-square" style="font-size: 1.5em;"></i><span>Create</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('editfullcover') }}">
                            <i class="bi bi-pencil-square" style="font-size: 1.5em;"></i><span>Manage</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#tables-navcccc" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-layout-text-window-reverse"></i><span>Daily Collection Reports</span><i class="bi-chevron-down ms-auto"></i>
                </a>
                <ul id="tables-navcccc" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('bysitesreports') }}">
                            <i class="bi bi-building" style="font-size: 1.5em;"></i><span>Site Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('collectionreports') }}">
                            <i class="bi bi-globe" style="font-size: 1.5em;"></i><span>Network Reports</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#tables-navcccc" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-layout-money"></i><span>Cash In Hand</span><i class="bi-chevron-down ms-auto"></i> 
                </a>
                <ul id="tables-navcccc" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('cash-in-hand-balances.index') }}">
                            <i class="bi bi-building" style="font-size: 1.5em;"></i><span>Balances</span>
                        </a>
                    </li>
                </ul>
            </li>
          
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-people"></i><span>Users</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="charts-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('teams.create') }}">
                            <i class="bi bi-person-plus" style="font-size: 1.5em;"></i><span>Create</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('teams.index') }}">
                            <i class="bi bi-person-lines-fill" style="font-size: 1.5em;"></i><span>Manage</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#charts-navz" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-share"></i><span>Networks</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="charts-navz" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('networks.index') }}">
                            <i class="bi bi-eye" style="font-size: 1.5em;"></i><span>View Networks</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('networks.create') }}">
                            <i class="bi bi-plus-circle" style="font-size: 1.5em;"></i><span>Create Network</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#charts-navzc" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-map"></i><span>Sites</span><i class="bi-chevron-down ms-auto"></i>
                </a>
                <ul id="charts-navzc" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('sites') }}">
                            <i class="bi bi-eye" style="font-size: 1.5em;"></i><span>View Sites</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('getsite') }}">
                            <i class="bi bi-plus-circle" style="font-size: 1.5em;"></i><span>Create Site</span>
                        </a>
                    </li>
                </ul>
            </li>
            
             <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#database-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-database"></i><span>Budgets</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="database-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('budgets.index') }}">
                            <i class="bi bi-bank" style="font-size: 1.5em;"></i><span>Target Review</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('budgets.create') }}">
                            <i class="bi bi-table" style="font-size: 1.5em;"></i><span>Enter Site Budgets</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('budgets.charts.usd') }}">
                            <i class="bi bi-cash-stack" style="font-size: 1.5em;"></i><span>USD Chart</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('budgets.charts.zwg') }}">
                            <i class="bi bi-wallet2" style="font-size: 1.5em;"></i><span>ZWG Chart</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#collection-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-newspaper"></i><span>Data Analytics</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="collection-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('ai.dashboard') }}">
                            <i class="bi bi-bar-chart-steps"></i>
                            <span>AI Analytics</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('reports.hub') }}">
                            <i class="bi bi-bar-chart-steps"></i>
                            <span>Reports Hub</span>
                        </a>
                    </li>
                </ul>
            </li>

        @endif

    </ul>

    <div class="sidebar-footer-note">
        Use the dashboard for trends, the collection center for daily submissions, and the reports area for operational review.
    </div>

</aside>
