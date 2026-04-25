package com.bikestore.order;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.client.RestTemplate;

import java.util.Map;

@RestController
@RequestMapping("/orders")
public class OrderGraphQLController {

    private final RestTemplate restTemplate;
    private final String productServiceUrl;

    public OrderGraphQLController(RestTemplate restTemplate,
                                  @Value("${product.service.url}") String productServiceUrl) {
        this.restTemplate = restTemplate;
        this.productServiceUrl = productServiceUrl;
    }

    @PostMapping("/graphql")
    public OrderResponse createOrderUsingGraphQL(@RequestBody OrderRequest request) {
        Map<String, Object> graphQLRequest = Map.of(
                "query",
                "query($id: ID!) { product(productId: $id) { productId name price } }",
                "variables",
                Map.of("id", request.productId())
        );

        Map response = restTemplate.postForObject(
                productServiceUrl + "/graphql",
                graphQLRequest,
                Map.class
        );

        Map data = (Map) response.get("data");
        Map productData = (Map) data.get("product");

        String productName = productData.get("name").toString();
        double unitPrice = ((Number) productData.get("price")).doubleValue();
        double totalPrice = unitPrice * request.quantity();

        return new OrderResponse(
                "O3001-GQL",
                request.customerId(),
                request.productId(),
                productName,
                request.quantity(),
                unitPrice,
                totalPrice,
                "CREATED_BY_GRAPHQL"
        );
    }
}
