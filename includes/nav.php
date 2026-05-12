<?php
// includes/nav.php
// Modular navigation menu component for Maraki Consultancy Management

function getNavigationItems(): array {
    return [
        ['key' => 'home', 'label' => 'Home', 'url' => 'index.php'],
        ['key' => 'services', 'label' => 'Services', 'url' => 'services.php'],
        ['key' => 'projects', 'label' => 'Projects', 'url' => 'projects.php'],
        ['key' => 'about', 'label' => 'About', 'url' => 'about.php'],
        ['key' => 'contact', 'label' => 'Contact', 'url' => 'contact.php'],
        ['key' => 'login', 'label' => 'Login', 'url' => 'login.php'],
        ['key' => 'register', 'label' => 'Register', 'url' => 'register.php'],
    ];
}

function renderNavItem(array $item, string $activeKey): string {
    $isActive = $item['key'] === $activeKey;
    $class = $isActive ? 'nav-link active' : 'nav-link';

    return sprintf(
        '<li class="nav-item"><a href="%s" class="%s">%s</a></li>',
        htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'),
        $class,
        htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8')
    );
}

function renderNavigationMenu(string $activeKey = 'home'): void {
    $items = getNavigationItems();

    echo '<nav class="main-nav">';
    echo '    <ul class="nav-list">';

    foreach ($items as $item) {
        echo renderNavItem($item, $activeKey);
    }

    echo '    </ul>';
    echo '</nav>';
}

function getNavigationStyles(): string {
    return <<<CSS
<style>
.main-nav {
    background-color: #0f172a;
    padding: 1rem;
}
.nav-list {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin: 0;
    padding: 0;
}
.nav-item {
}
.nav-link {
    color: #cbd5e1;
    text-decoration: none;
    padding: 0.5rem 0.85rem;
    border-radius: 0.35rem;
    transition: background-color 0.2s ease-in-out;
}
.nav-link:hover {
    background-color: rgba(255, 255, 255, 0.08);
}
.nav-link.active {
    background-color: #1e293b;
    color: #ffffff;
}
@media (max-width: 640px) {
    .nav-list {
        flex-direction: column;
    }
}
</style>
CSS;
}

function renderNavigationMenuWithStyles(string $activeKey = 'home'): void {
    echo getNavigationStyles();
    renderNavigationMenu($activeKey);
}
