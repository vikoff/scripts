<?php

function buildChildrenList(simple_html_dom_node $tag, $level = 0)
{
    foreach ($tag->children as $child) {

        echo getTagInfo($child, $level);

        if ($child->children && $level < 32) {
            buildChildrenList($child, $level + 1);
        }
    }
}

function getTagInfo(simple_html_dom_node $tag, $level = 0)
{
    if ($tag->nodetype == HDOM_TYPE_COMMENT) {
        return '';
    }

    if ($tag->nodetype == HDOM_TYPE_UNKNOWN) {
        return $tag."\n";
    }

    $id = '';
    $class = '';
    $attrs = '';
    foreach ($tag->attr as $k => $v) {
        $k = strtolower($k);

        // skip data-* attrs
        if (substr($k, 0, 5) == 'data-')
            continue;

        if ($k == 'href' || $k == 'src')
            $v = '...';

//        if ($k == 'id') {
//            $id .= "#$v";
//        } elseif ($k == 'class') {
//            $class .= ".$v";
//        } else {
        $attrs .= '[' . $k . '="' . str_replace('"', '&quot;', $v) . '"]';
//        }
    }

    $tab = '';
    for ($i = 0; $i < $level; $i++)
        $tab .= ($i && $i % 5 == 0) ? '- ' : '- ';

//    $tab = str_repeat('- ', $level);

    return "$tab$tag->tag$id$class$attrs\n";
}
