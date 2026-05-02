@extends('layouts.frontpages')

@section('title')
GRUMA
@endsection

@section('content')
<section class="front-hero">
  <div class="front-hero-card">
    <div class="hero-spotlight">Insurance Operations • Collections • Reporting</div>
    <h1 class="front-display-title">Run the <span>entire branch workflow</span> from one focused workspace.</h1>
    <p>
      GRUMA brings daily collections, face value allocation, site reporting, amendments, and budget oversight into a single operational view designed for clerks, supervisors, managers, and super users.
    </p>

    <div class="front-hero-actions">
      <a href="{{ route('login') }}" class="btn btn-primary">Open Secure Workspace</a>
      <a href="#capabilities" class="btn btn-secondary">Explore Capabilities</a>
    </div>
  </div>

  <div class="front-side-stack">
    <div class="front-side-panel">
      <h3>Operational Visibility</h3>
      <p class="mb-0">Track USD and ZWG collections, deposits, face values, amendments, and trends with less manual chasing.</p>
    </div>
    <div class="front-metric-grid">
      <div class="front-metric-card">
        <span class="metric-label"><i class="bi bi-receipt"></i> Daily collections</span>
        <strong class="metric-value">USD + ZWG</strong>
        <div class="metric-note">Single workflow for cash, swipe, transfers, MPOS, and deposits.</div>
      </div>
      <div class="front-metric-card">
        <span class="metric-label"><i class="bi bi-stack"></i> Face values</span>
        <strong class="metric-value">Serialized control</strong>
        <div class="metric-note">Receive, allocate, declare, and review stock movement by user.</div>
      </div>
    </div>
  </div>
</section>

<section id="capabilities" class="front-section">
  <div class="front-section-heading">
    <div>
      <h2>Built for field operations and oversight</h2>
      <p>The application is structured around the work actually happening in branches, not generic back-office screens.</p>
    </div>
  </div>

  <div class="front-feature-grid">
    <article class="front-feature-card">
      <div class="feature-icon"><i class="bi bi-cash-coin"></i></div>
      <h3>Daily Collection Capture</h3>
      <p>Clerks record detailed USD and ZWG breakdowns including premiums, ZINARA fees, bank deposits, and cash-in-hand positions.</p>
    </article>

    <article class="front-feature-card">
      <div class="feature-icon"><i class="bi bi-upc-scan"></i></div>
      <h3>Face Value Governance</h3>
      <p>Supervisors receive stock by serial range, allocate it to clerks, and maintain traceable stock balances and histories.</p>
    </article>

    <article class="front-feature-card">
      <div class="feature-icon"><i class="bi bi-bar-chart-line"></i></div>
      <h3>Actionable Reporting</h3>
      <p>See daily, site, network, cumulative, and user-level reporting with visual summaries that surface operational trends quickly.</p>
    </article>

    <article class="front-feature-card">
      <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
      <h3>Controls and Review</h3>
      <p>Managers and super users can review amendment requests, compare budgets to actuals, and maintain networks, sites, roles, and users.</p>
    </article>
  </div>
</section>

<section class="front-section">
  <div class="front-section-heading">
    <div>
      <h2>Core workflow</h2>
      <p>A single operational rhythm from stock receipt to reporting.</p>
    </div>
  </div>

  <div class="surface-grid two-up">
    <div class="front-feature-card">
      <h3>1. Capture and control</h3>
      <p>Receive serialized face values, distribute them to clerks, and capture daily premium activity with deposit and balance context.</p>
    </div>
    <div class="front-feature-card">
      <h3>2. Review and improve</h3>
      <p>Use charts, tables, user reports, and amendment workflows to detect gaps, validate submissions, and keep branch performance visible.</p>
    </div>
  </div>
</section>
@endsection
