# Instrumenting Lambda custom runtimes - PHP Example


### Reference links
[Custom AWS Lambda runtimes](https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html)

[AWS Serverless Application Model (SAM)](https://github.com/awslabs/serverless-application-model/blob/master/versions/2016-10-31.md)

[Input](https://docs.aws.amazon.com/apigateway/latest/developerguide/set-up-lambda-proxy-integrations.html#api-gateway-simple-proxy-for-lambda-input-format)

[Output](https://docs.aws.amazon.com/apigateway/latest/developerguide/set-up-lambda-proxy-integrations.html#api-gateway-simple-proxy-for-lambda-output-format)

[XRay Segments](https://docs.aws.amazon.com/xray/latest/devguide/xray-api-segmentdocuments.html)


```json
{
    "datadog": {
        "trace": {
            "trace-id": "tttttt",
            "sampling-priority": "1",
            "parent-id": "ssssss"
        }
    }
}
```
