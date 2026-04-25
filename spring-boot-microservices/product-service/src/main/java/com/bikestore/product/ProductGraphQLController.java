package com.bikestore.product;

import org.springframework.graphql.data.method.annotation.Argument;
import org.springframework.graphql.data.method.annotation.QueryMapping;
import org.springframework.stereotype.Controller;

@Controller
public class ProductGraphQLController {

    @QueryMapping
    public Product product(@Argument String productId) {
        return new Product(productId, "Mountain Bike", 450.0);
    }
}
