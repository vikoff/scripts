<?php
$menuItems = array(
    'main' => array('title' => 'Main', 'url' => href('sql/view/'.$this->session['id'])),
    'charts' => array('title' => 'Charts', 'url' => href('sql/view-charts/'.$this->session['id'])),
    'group' => array('title' => 'Grouping', 'url' => href('sql/view-group/'.$this->session['id'])),
    'can-group' => array('title' => 'Cannonical Grouping', 'url' => href('sql/view-can-group/'.$this->session['id'])),
);
$menuHtml = array();
foreach ($menuItems as $key => $data) {
    $isActive = $this->page == $key;
    $menuHtml[] = '<a href="'.$data['url'].'" '.($isActive ? 'class="active"' : '').'>'.$data['title'].'</a>';
}

?>
<p><a href="<?= href('sql'); ?>">Back To List</a></p>
<nav>
    <?= implode(' | ', $menuHtml); ?>
</nav>
