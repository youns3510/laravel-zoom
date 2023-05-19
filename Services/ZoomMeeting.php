<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class ZoomMeeting
{
  protected string $accessToken;
  protected $client;
  public function __construct()
  {


    if (!Cache::has('zoom_access_token')) {
      $this->accessToken = Cache::remember(
        'zoom_access_token',
        60 * 60,
        function () {
          return $this->getAccessToken();
        }
      );
    } else {
      $this->accessToken = cache('zoom_access_token');
    }


    $this->client = new Client([
      'base_uri' => 'https://api.zoom.us/v2/',
      'headers' => [
        'Authorization' => 'Bearer ' . $this->accessToken,
        'Content-Type' => 'application/json',
      ],
    ]);
  }


  protected function getAccessToken()
  {


    $account_id = env('ZOOM_ACCOUNT_ID');
    $client_id = env('ZOOM_CLIENT_ID');
    $client_secret = env('ZOOM_CLIENT_SECRET');

    if (!$account_id || !$client_id || !$client_secret) {
      throw new \Exception('Zoom account_id, client_id and client_secret must be set in ');
    }



    $this->client = new Client([
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
        'Host' => 'zoom.us',
      ],
    ]);




    $response = $this->client->request('POST', "https://zoom.us/oauth/token", [
      'form_params' => [
        'grant_type' => 'account_credentials',
        'account_id' => $account_id,
      ],
    ]);

    $responseBody = json_decode($response->getBody(), true);


    return $responseBody['access_token'];
  }






  /**
   * Create a new meeting.
   *
   * @param array $data
   * @return array
   */
  public function createMeeting(array $data)
  {

    $response = $this->client->request('POST', 'users/me/meetings', [
      'json' => $data,
    ]);

    return json_decode($response->getBody(), true);
  }

  /**
   * Get a meeting by ID.
   *
   * @param string $meetingId
   * @return array
   */
  public function getMeeting(string $meetingId)
  {


    $response = $this->client->request('GET', 'meetings/' . $meetingId);

    return json_decode($response->getBody(), true);
  }




  /**
   * Get all meetings.
   *
   * @return array
   */
  public function getAllMeeting()
  {
    $response = $this->client->request('GET', 'users/me/meetings');

    return json_decode($response->getBody(), true);
  }

  /**
   * Get upcoming meetings.
   *
   * @return array
   */
  public function getUpcomingMeeting()
  {


    $response = $this->client->request('GET', 'users/me/meetings?type=upcoming');

    return json_decode($response->getBody(), true);
  }

  /**
   * Get archived meetings.
   *
   * @return array
   */
  public function getPreviousMeetings()
  {


    $meetings = $this->getAllMeeting();

    $previousMeetings = [];

    // dd($meetings);
    foreach ($meetings['meetings'] as $meeting) {
      $start_time = strtotime($meeting['start_time']);

      if ($start_time < time()) {
        $previousMeetings[] = $meeting;
      }
    }

  
    // $response = $this->client->request('GET', 'users/me/meetings?type=past');

    return $previousMeetings;
  }

  /**
   * Reschedule a meeting.
   *
   * @param string $meetingId
   * @param array $data
   * @return array
   */
  public function rescheduleMeeting(string $meetingId, array $data)
  {


    $response = $this->client->request('PATCH', 'meetings/' . $meetingId, [
      'json' => $data,
    ]);

    return json_decode($response->getBody(), true);
  }





  /**
   * End a Zoom meeting.
   *
   * @param string $meetingId The ID of the meeting to end.
   * @return bool True if the meeting was successfully ended, false otherwise.
   */
  public function endMeeting($meetingId)
  {


    $response = $this->client->request('PUT', 'meetings/' . $meetingId . '/status', [
      'json' => [
        'action' => 'end',
      ],
    ]);

    return $response->getStatusCode() === 204;
  }

  /**
   * Delete a meeting by ID.
   *
   * @param string $meetingId
   * @return bool
   */
  public function deleteMeeting(string $meetingId)
  {


    $response = $this->client->request('DELETE', 'meetings/' . $meetingId);

    return $response->getStatusCode() === 204;
  }




  /**
   * recover a Zoom meeting.
   *
   * @param string $meetingId The ID of the meeting to end.
   * @return bool True if the meeting was successfully ended, false otherwise.
   */
  public function recoverMeeting($meetingId)
  {


    $response = $this->client->request('PUT', 'meetings/' . $meetingId . '/status', [
      'json' => [
        'action' => 'recover',
      ],
    ]);

    return $response->getStatusCode() === 204;
  }
}
