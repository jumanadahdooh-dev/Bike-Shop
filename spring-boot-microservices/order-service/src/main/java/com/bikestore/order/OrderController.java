package com.bikestore.order;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.client.RestTemplate;

@RestController
@RequestMapping("/orders")
public class OrderController {

    private final RestTemplate restTemplate;
    private final String productServiceUrl;

    public OrderController(RestTemplate restTemplate,
                           @Value("${product.service.url}") String productServiceUrl) {
        this.restTemplate = restTemplate;
        this.productServiceUrl = productServiceUrl;
    }

    @PostMapping
    public OrderResponse createOrder(@RequestBody OrderRequest request) {
        Product product = restTemplate.getForObject(
                productServiceUrl + "/products/" + request.productId(),
                Product.class
        );

        double totalPrice = product.price() * request.quantity();

        return new OrderResponse(
                "O3001",
                request.customerId(),
                request.productId(),
                product.name(),
                request.quantity(),
                product.price(),
                totalPrice,
                "CREATED"
        );
    }
}

@Configuration
class RestTemplateConfig {
    @Bean
    RestTemplate restTemplate() {
        return new RestTemplate();
    }
}

record OrderRequest(String customerId, String productId, int quantity) {}

record Product(String productId, String name, double price) {}

record OrderResponse(
        String orderId,
        String customerId,
        String productId,
        String productName,
        int quantity,
        double unitPrice,
        double totalPrice,
        String status
) {}
