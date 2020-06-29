import json
import logging
import socket
import requests
from datadog_lambda.wrapper import datadog_lambda_wrapper

logger = logging.getLogger()
logger.setLevel(logging.INFO)

@datadog_lambda_wrapper
def lambda_handler(event, context):
    logger.info("Processing entrypoint using AWS Lambda")
    r = requests.get("https://php.aws.pipsquack.ca/demo/php", timeout=None)
    logger.info("Completed entrypoint")
    return {
        'statusCode': r.status_code,
        'body': r.text
    }
