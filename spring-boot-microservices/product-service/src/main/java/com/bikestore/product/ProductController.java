package com.bikestore.product;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/products")
public class ProductController {

    @GetMapping("/{productId}")
    public Product getProduct(@PathVariable String productId) {
        return new Product(productId, "Mountain Bike", 450.0);
    }
}

record Product(String productId, String name, double price) {}
