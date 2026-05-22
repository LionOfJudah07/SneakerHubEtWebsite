<?php

/**
 * Session management functions
 * Note: Flash message functions already exist in functions.php
 * These functions have different names to avoid conflicts
 */

// Use different function names for session operations
function session_set_flash($type, $message)
{
    $_SESSION['flash_' . $type] = $message;
}

function session_get_flash($type)
{
    if (isset($_SESSION['flash_' . $type])) {
        $message = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $message;
    }
    return null;
}

function session_has_flash($type)
{
    return isset($_SESSION['flash_' . $type]);
}

function session_set_form_data($data)
{
    $_SESSION['form_data'] = $data;
}

function session_get_form_data($field = null)
{
    if (isset($_SESSION['form_data'])) {
        $data = $_SESSION['form_data'];
        if ($field && isset($data[$field])) {
            $value = $data[$field];
            unset($_SESSION['form_data'][$field]);
            return $value;
        } elseif (!$field) {
            $all_data = $data;
            unset($_SESSION['form_data']);
            return $all_data;
        }
    }
    return null;
}

function session_clear_form_data()
{
    if (isset($_SESSION['form_data'])) {
        unset($_SESSION['form_data']);
    }
}

function session_set_error($message)
{
    $_SESSION['error'] = $message;
}

function session_get_error()
{
    if (isset($_SESSION['error'])) {
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
        return $error;
    }
    return null;
}

function session_set_success($message)
{
    $_SESSION['success'] = $message;
}

function session_get_success()
{
    if (isset($_SESSION['success'])) {
        $success = $_SESSION['success'];
        unset($_SESSION['success']);
        return $success;
    }
    return null;
}

function session_add_to_cart($product_id, $quantity = 1, $size = null, $color = null)
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $key = $product_id . '_' . $size . '_' . $color;

    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'size' => $size,
            'color' => $color,
            'added_at' => time()
        ];
    }

    session_update_cart_count();
}

function session_remove_from_cart($key)
{
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
        session_update_cart_count();
        return true;
    }
    return false;
}

function session_update_cart_quantity($key, $quantity)
{
    if (isset($_SESSION['cart'][$key]) && $quantity > 0) {
        $_SESSION['cart'][$key]['quantity'] = $quantity;
        session_update_cart_count();
        return true;
    }
    return false;
}

function session_get_cart()
{
    return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
}

function session_clear_cart()
{
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
    if (isset($_SESSION['cart_count'])) {
        unset($_SESSION['cart_count']);
    }
}

function session_get_cart_count()
{
    if (isset($_SESSION['cart_count'])) {
        return $_SESSION['cart_count'];
    }

    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    $_SESSION['cart_count'] = $count;
    return $count;
}

function session_update_cart_count()
{
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    $_SESSION['cart_count'] = $count;
}

function session_add_to_wishlist($product_id)
{
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }

    if (!in_array($product_id, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = $product_id;
    }
}

function session_remove_from_wishlist($product_id)
{
    if (isset($_SESSION['wishlist'])) {
        $key = array_search($product_id, $_SESSION['wishlist']);
        if ($key !== false) {
            unset($_SESSION['wishlist'][$key]);
            $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
        }
    }
}

function session_get_wishlist()
{
    return isset($_SESSION['wishlist']) ? $_SESSION['wishlist'] : [];
}

function session_is_in_wishlist($product_id)
{
    if (isset($_SESSION['wishlist'])) {
        return in_array($product_id, $_SESSION['wishlist']);
    }
    return false;
}

function session_set_checkout_data($data)
{
    $_SESSION['checkout_data'] = $data;
}

function session_get_checkout_data()
{
    return isset($_SESSION['checkout_data']) ? $_SESSION['checkout_data'] : null;
}

function session_clear_checkout_data()
{
    if (isset($_SESSION['checkout_data'])) {
        unset($_SESSION['checkout_data']);
    }
}
