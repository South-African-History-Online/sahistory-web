# SAHO Schema.org API Documentation

## Overview

The SAHO Schema.org API provides programmatic access to structured metadata for all SAHO content. This RESTful JSON API enables external systems, AI applications, and research tools to retrieve machine-readable data about South African history.

**Base URL**: `https://sahistory.org.za`
**API Version**: 1.0
**Format**: JSON-LD (application/ld+json)
**Authentication**: None required (public API)
**Rate Limit**: 100 requests/minute per IP
**CORS**: Enabled (Access-Control-Allow-Origin: *)

## Endpoints

### 1. Get Node Schema

Retrieve complete Schema.org JSON-LD for a specific node.

**Endpoint**: `GET /api/schema/{node_id}`

**Parameters**:
- `node_id` (required): The Drupal node ID (integer)

**Response**: 200 OK
```json
{
  "schema": {
    "@context": "https://schema.org",
    "@type": "ScholarlyArticle",
    "headline": "Article Title",
    "author": [
      {"@type": "Person", "name": "Author Name"}
    ],
    "datePublished": "2024-01-15T10:30:00+02:00",
    "publisher": {
      "@type": "Organization",
      "name": "South African History Online"
    },
    "license": "https://creativecommons.org/licenses/by-nc-sa/4.0/",
    "isAccessibleForFree": true
  },
  "meta": {
    "node_id": 12345,
    "node_type": "article",
    "title": "Article Title",
    "url": "https://sahistory.org.za/article/...",
    "generated_at": "2026-02-01T14:30:00+00:00",
    "api_version": "1.0"
  }
}
```

**Error Responses**:

404 Not Found - Node does not exist or is unpublished:
```json
{
  "error": "Node not found or not published."
}
```

404 Not Found - Content type not supported:
```json
{
  "error": "Schema.org data not available for this content type.",
  "node_type": "page",
  "node_id": 12345
}
```

**Headers**:
- `Content-Type: application/ld+json; charset=UTF-8`
- `Access-Control-Allow-Origin: *`
- `Cache-Control: public, max-age=3600`
- `X-Robots-Tag: noindex`

**Example Request**:
```bash
curl https://sahistory.org.za/api/schema/13830
```

**Example with jq**:
```bash
curl -s https://sahistory.org.za/api/schema/13830 | jq '.schema'
```

### 2. Get Schema Type

Retrieve only the Schema.org type information for a node (lightweight endpoint).

**Endpoint**: `GET /api/schema/{node_id}/type`

**Parameters**:
- `node_id` (required): The Drupal node ID (integer)

**Response**: 200 OK
```json
{
  "@context": "https://schema.org",
  "@type": "ScholarlyArticle",
  "node_type": "article",
  "node_id": 12345,
  "url": "https://sahistory.org.za/article/..."
}
```

**Headers**:
- `Content-Type: application/json`
- `Access-Control-Allow-Origin: *`
- `Cache-Control: public, max-age=86400`

**Use Case**: Quickly determine if a node supports rich snippets without fetching full schema.

**Example Request**:
```bash
curl https://sahistory.org.za/api/schema/13830/type
```

### 3. List Schema Types

Get a list of all available Schema.org types and content counts.

**Endpoint**: `GET /api/schema/types`

**Response**: 200 OK
```json
{
  "schema_types": {
    "article": "ScholarlyArticle",
    "biography": "Person",
    "event": "Event",
    "archive": "ArchiveComponent",
    "place": "Place",
    "product": "Book",
    "image": "ImageObject",
    "gallery_image": "ImageObject"
  },
  "content_counts": {
    "article": 2809,
    "biography": 10772,
    "event": 17656,
    "archive": 29986,
    "place": 1838,
    "product": 16,
    "image": 18264,
    "gallery_image": 0
  },
  "total_nodes": 81341,
  "api_endpoints": {
    "node_schema": "/api/schema/{node_id}",
    "node_type": "/api/schema/{node_id}/type",
    "list_types": "/api/schema/types"
  },
  "documentation": {
    "llm_txt": "/llm.txt",
    "citation_api": "/api/citation/{node_id}"
  }
}
```

**Headers**:
- `Content-Type: application/json`
- `Access-Control-Allow-Origin: *`
- `Cache-Control: public, max-age=3600`

**Use Case**: Discovery endpoint for applications to understand available content types.

**Example Request**:
```bash
curl https://sahistory.org.za/api/schema/types | jq .
```

## Content Type Mapping

| Drupal Type     | Schema.org Type              | Count   | Rich Snippets |
|-----------------|------------------------------|---------|---------------|
| article         | ScholarlyArticle             | 2,809   | Yes           |
| biography       | Person                       | 10,772  | Yes           |
| event           | Event                        | 17,656  | Yes           |
| archive         | ArchiveComponent             | 29,986  | Partial       |
| place           | Place/Museum/Landmarks       | 1,838   | Yes           |
| product         | Book                         | 16      | Yes           |
| image           | ImageObject                  | 18,264  | No            |
| gallery_image   | ImageObject                  | 0       | No            |

**Total**: 81,341 nodes with Schema.org markup

## Response Format

All API responses follow this structure:

### Success Response
```json
{
  "schema": { ... },    // Schema.org JSON-LD object
  "meta": { ... }       // Metadata about the response
}
```

### Error Response
```json
{
  "error": "Error message",
  "node_type": "string",    // Optional
  "node_id": 123            // Optional
}
```

## Caching

**API-Level Caching**:
- Node schemas: 1 hour (3600 seconds)
- Type listings: 1 hour (3600 seconds)
- Schema type only: 24 hours (86400 seconds)

**Client-Side Caching**:
- Respect `Cache-Control` headers
- Use `ETag` for conditional requests
- Maximum recommended cache: 1 hour

**Cache Invalidation**:
- Automatic on node updates
- Manual: Clear Drupal cache (`drush cr`)

## Rate Limiting

**Limits**:
- 100 requests per minute per IP address
- Burst allowance: 10 requests

**Headers** (rate limit info):
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1643723400
```

**429 Too Many Requests**:
```json
{
  "error": "Rate limit exceeded. Try again in 60 seconds.",
  "retry_after": 60
}
```

**Best Practices**:
- Implement exponential backoff
- Cache responses locally
- Use bulk operations when possible
- Consider off-peak hours for large crawls

## CORS Support

All API endpoints support Cross-Origin Resource Sharing (CORS):

**Allowed Origins**: `*` (all origins)
**Allowed Methods**: `GET`, `OPTIONS`
**Allowed Headers**: `Content-Type`
**Max Age**: 86400 seconds (24 hours)

**Preflight Request** (OPTIONS):
```bash
curl -X OPTIONS \
  -H "Origin: https://example.com" \
  -H "Access-Control-Request-Method: GET" \
  https://sahistory.org.za/api/schema/12345
```

## Error Handling

### HTTP Status Codes

| Code | Meaning                  | Action                          |
|------|--------------------------|---------------------------------|
| 200  | Success                  | Process response               |
| 404  | Not Found                | Check node ID and publication status |
| 429  | Too Many Requests        | Implement rate limiting        |
| 500  | Internal Server Error    | Retry with exponential backoff |
| 503  | Service Unavailable      | Try again later                |

### Error Messages

All errors include descriptive messages:
```json
{
  "error": "Human-readable error description",
  "code": "ERROR_CODE",           // Optional
  "details": { ... }              // Optional additional info
}
```

## Authentication

**Current**: No authentication required (public API)

**Future**: API keys may be required for high-volume access. Contact api@sahistory.org.za for enterprise access.

## Use Cases

### 1. AI/LLM Citation Systems

Retrieve structured data for AI-generated citations:

```python
import requests

def get_saho_citation(node_id):
    url = f"https://sahistory.org.za/api/schema/{node_id}"
    response = requests.get(url)
    data = response.json()

    schema = data['schema']
    return {
        'title': schema.get('headline') or schema.get('name'),
        'authors': schema.get('author', []),
        'date': schema.get('datePublished'),
        'url': data['meta']['url'],
        'source': 'South African History Online'
    }
```

### 2. Educational Platforms

Integrate SAHO content into learning management systems:

```javascript
async function loadHistoricalEvent(nodeId) {
  const response = await fetch(
    `https://sahistory.org.za/api/schema/${nodeId}`
  );
  const data = await response.json();

  return {
    title: data.schema.name,
    date: data.schema.startDate,
    description: data.schema.description,
    image: data.schema.image?.url,
    location: data.schema.location?.name
  };
}
```

### 3. Research Data Aggregation

Bulk download for academic research:

```bash
#!/bin/bash
# Download all biography schemas

curl -s https://sahistory.org.za/api/schema/types | \
  jq -r '.content_counts.biography' | \
  xargs -I {} seq 1 {} | \
  while read i; do
    curl -s "https://sahistory.org.za/api/schema/$i" \
      > "biography_$i.json"
    sleep 1  # Rate limiting
  done
```

### 4. SEO/Meta Tag Generation

Generate meta tags for content aggregators:

```php
<?php
function generateMetaTags($nodeId) {
  $url = "https://sahistory.org.za/api/schema/$nodeId";
  $data = json_decode(file_get_contents($url), true);
  $schema = $data['schema'];

  return [
    'og:title' => $schema['headline'] ?? $schema['name'],
    'og:description' => $schema['description'] ?? $schema['abstract'],
    'og:image' => $schema['image']['url'] ?? '',
    'og:type' => 'article',
    'article:published_time' => $schema['datePublished'],
  ];
}
```

## Integration Examples

### Python (requests)

```python
import requests

# Get article schema
response = requests.get('https://sahistory.org.za/api/schema/13830')
data = response.json()
print(data['schema']['headline'])

# Get all types
response = requests.get('https://sahistory.org.za/api/schema/types')
types = response.json()
print(f"Total nodes: {types['total_nodes']}")
```

### JavaScript (fetch)

```javascript
// Get event schema
fetch('https://sahistory.org.za/api/schema/10782')
  .then(res => res.json())
  .then(data => {
    console.log('Event:', data.schema.name);
    console.log('Date:', data.schema.startDate);
  });

// Get type counts
fetch('https://sahistory.org.za/api/schema/types')
  .then(res => res.json())
  .then(data => {
    Object.entries(data.content_counts).forEach(([type, count]) => {
      console.log(`${type}: ${count} nodes`);
    });
  });
```

### cURL

```bash
# Get biography schema with pretty printing
curl -s https://sahistory.org.za/api/schema/7765 | jq '.schema'

# Get just the Schema.org @type
curl -s https://sahistory.org.za/api/schema/7765/type | jq '.["@type"]'

# Download schema to file
curl -o schema.json https://sahistory.org.za/api/schema/13830

# Check response headers
curl -I https://sahistory.org.za/api/schema/13830
```

## Complementary APIs

### Citation API

Generate formatted citations for SAHO content.

**Endpoint**: `GET /api/citation/{node_id}`

**Response Formats**: APA, MLA, Chicago, Harvard, Vancouver

### llm.txt

AI/LLM discovery file following 2026 specification.

**Endpoint**: `GET /llm.txt`

**Format**: Plain text

## Support & Contact

**API Issues**: api@sahistory.org.za
**Technical Support**: webmaster@sahistory.org.za
**General Inquiries**: info@sahistory.org.za

**Documentation**:
- Schema.org Reference: `/modules/custom/saho_tools/SCHEMA_ORG.md`
- API Documentation: `/modules/custom/saho_tools/API.md`

## Changelog

### v1.0 (February 2026)
- Initial release
- Support for 7 content types
- 81,341+ nodes with Schema.org markup
- CORS enabled
- Rate limiting implemented

---

**Last Updated**: February 2026
**API Version**: 1.0
**Maintained by**: SAHO Development Team
